<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.4.0
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 */

namespace Mathnews\WP\Core\Admin;

use Mathnews\WP\Core;
use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;

Utils::require_core('trait-mathnews-core-audit.php');

/**
 * Initialize auditing hooks.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class AuditInit {
	use Core\Audit;

	public function __construct() {
		add_action('mn_audit_clear_entries', array($this, 'clear_entries'));
		// clear old entries whenever we update retention time
		add_action('update_option_mn_audit_persist_days', array($this, 'clear_entries'));
		add_action('mathnews-core:add_settings', array($this, 'register_settings'));

		add_action('deleted_post', array($this, 'audit_post_delete'), 10, 2);
		add_action('transition_post_status', array($this, 'audit_post_update_status'), 10, 3);
		add_filter('activated_plugin', array($this, 'audit_plugin_create'));
		add_filter('deactivated_plugin', array($this, 'audit_plugin_delete'));
		add_action('user_register', array($this, 'audit_user_create'), 10, 2);
		add_action('deleted_user', array($this, 'audit_user_delete'), 10, 3);
		add_action('set_user_role', array($this, 'audit_user_update_role'), 10, 3);
		add_action('retrieve_password', array($this, 'audit_user_update_reset_password'));
	}

	/**
	 * Scheduled function to clear old log entries.
	 *
	 * @since 1.4.0
	 * @uses mn_audit_ckear_entries
	 * @uses update_option_mn_audit_persist_days
	 */
	public function clear_entries() {
		global $wpdb;

		$interval = get_option('mn_audit_persist_days', 90);

		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$this->audit_table} WHERE log_time < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
			[$interval]
		));
	}

	/**
	 * Register option to set the retention time.
	 *
	 * @since 1.4.0
	 * @uses mathnews-core:add_settings
	 */
	public function register_settings($settings) {
		$settings->add_section('audit', 'Audit log', ['tab' => 'advanced'])
			->register(
				'mn_audit_persist_days',
				__('Keep logs for','textdomain'),
				'text',
				90,
				[
					'type' => 'number',
					'after_text' => ' days',
					'attrs' => [
						'min' => 30,
						'max' => 365,
						'size' => 1,
					],
				]
			);
	}

	/**
	 * Audit post deletion.
	 *
	 * @since 1.4.0
	 * @uses deleted_post
	 */
	public function audit_post_delete(int $postid, \WP_Post $post) {
		$cur_user = get_current_user_id();

		if ($cur_user === 0 || !current_user_can('delete_others_posts')) {
			// probably a scheduled call. abort.
			return;
		}

		// ignore actions taken by the post's author
		// non-strict comparison since WP_Post::post_author is a string
		if ($cur_user == $post->post_author) {
			return;
		}

		$this->audit_without_target('post.delete', $cur_user, [
			'post_title' => $post->post_title,
		]);
	}

	/**
	 * Audit post status changes.
	 *
	 * @since 1.4.0
	 * @uses transition_post_status
	 */
	public function audit_post_update_status(string $new_status, string $old_status, \WP_Post $post) {
		$cur_user = get_current_user_id();

		// ignore if statuses are the same
		if ($new_status === $old_status) {
			return;
		}

		// ignore actions taken by the post's author
		// non-strict comparison since WP_Post::post_author is a string
		if ($cur_user == $post->post_author) {
			return;
		}

		// ignore if this is the result of an article approval/rejection, since the post.approve/post.reject events
		// already cover this
		if (isset($_POST['mn-approve']) || isset($_POST['mn-reject-draft'])) {
			return;
		}

		$this->audit('post.update.status', $cur_user, $post->ID, [
			'new_status' => $new_status,
			'old_status' => $old_status,
		]);
	}

	/**
	 * Audit plugin activation.
	 *
	 * @since 1.4.0
	 * @uses activated_plugin
	 */
	public function audit_plugin_create(string $plugin) {
		$this->audit_without_target('plugin.create', get_current_user_id(), [
			'plugin_location' => $plugin,
		]);
	}

	/**
	 * Audit post deactivation.
	 *
	 * @since 1.4.0
	 * @uses deactivated_plugin
	 */
	public function audit_plugin_delete(string $plugin) {
		$this->audit_without_target('plugin.delete', get_current_user_id(), [
			'plugin_location' => $plugin,
		]);
	}

	/**
	 * Audit user registration.
	 *
	 * @since 1.4.0
	 * @uses user_register
	 */
	public function audit_user_create(int $user_id, array $userdata) {
		$this->audit('user.create', get_current_user_id(), $user_id, [
			'role' => $userdata['role'],
		]);
	}

	/**
	 * Audit user deletion.
	 *
	 * @since 1.4.0
	 * @uses deleted_user
	 */
	public function audit_user_delete(int $id, ?int $reassign, \WP_User $user) {
		$message = [];

		if (!empty($reassign)) {
			$message['reassigned_user'] = (new \WP_User($reassign))->user_login;
		}

		$this->audit('user.delete', get_current_user_id(), $id, $message);
	}

	/**
	 * Audit user role changes.
	 *
	 * @since 1.4.0
	 * @uses set_user_role
	 */
	public function audit_user_update_role(int $user_id, string $role, array $old_roles) {
		$user = new \WP_User($user_id);

		// don't run on adding a new user, this is already covered by user.create
		if (!empty($_POST['createuser'])) {
			return;
		}

		$this->audit('user.update.role', get_current_user_id(), $user_id, [
			'new_role' => $role,
			'old_roles' => $old_roles,
		]);
	}

	/**
	 * Audit user password resets.
	 *
	 * @since 1.4.0
	 * @uses retrieve_password
	 */
	public function audit_user_update_reset_password(string $user_login) {
		$user = new \WP_User($user_login);

		$this->audit('user.update.reset_password', get_current_user_id(), $user->ID, []);
	}
}
