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
		add_action('add_option_mn_audit_persist_days', array($this, 'clear_entries'));
		add_action('update_option_mn_audit_persist_days', array($this, 'clear_entries'));
		add_action('mathnews-core:add_settings', array($this, 'register_settings'));

		add_action('deleted_post', array($this, 'audit_post_delete'), 10, 2);
		add_filter('wp_insert_post_data', array($this, 'audit_post_update'), 10, 4);
		add_filter('activated_plugin', array($this, 'audit_plugin_create'));
		add_filter('deactivated_plugin', array($this, 'audit_plugin_delete'));
		add_action('added_option', array($this, 'audit_settings_update'), 10, 2);
		add_action('updated_option', array($this, 'audit_settings_update'), 10, 3);
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
			"DELETE FROM {$this->audit_table()} WHERE log_time < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
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

		// only audit posts and pages
		if (!in_array($post->post_type, ['page', 'post'])) {
			return $data;
		}

		// ignore actions taken by the post's author
		// non-strict comparison since WP_Post::post_author is a string
		if ($post->post_type === 'post' && $cur_user == $post->post_author) {
			return;
		}

		$this->audit_without_target("{$post->post_type}.delete", $cur_user, [
			'post_title' => $post->post_title,
		]);
	}

	/**
	 * Get the name of a category from its ID or slug.
	 *
	 * @param int|string $category The ID or slug of the category.
	 *
	 * @since 1.4.0
	 * @access private
	 */
	static private function get_category_name($category) {
		return get_category($category)->name;
	}

	/**
	 * Get the name of a tag from its ID or slug.
	 *
	 * @param int|string $tag The ID or slug of the tag.
	 *
	 * @since 1.4.0
	 * @access private
	 */
	static private function get_tag_name($tag) {
		return get_tag($tag)->name;
	}

	/**
	 * Audit post updates.
	 *
	 * @since 1.4.0
	 * @uses wp_insert_post_data
	 */
	public function audit_post_update(array $data, array $postarr, array $_, bool $update) {
		// only audit posts and pages
		if (!in_array($postarr['post_type'], ['page', 'post'])) {
			return $data;
		}

		// don't audit new posts, there's no reason to
		if (($postarr['post_type'] === 'post' && !$update) || empty($postarr['ID'])) {
			return $data;
		}

		$post = get_post($postarr['ID']);
		$cur_user = get_current_user_id();

		// perform specific actions if this was an approve or reject
		$action = 'post.update';
		$message = [];

		if (isset($_POST['mn-approve'])) {
			$action = 'post.approve';
		} else if (isset($_POST['mn-reject'])) {
			$action = 'post.reject';
			$reject_rationale = isset($_POST['mn-reject-rationale']) ? $_POST['mn-reject-rationale'] : '';
			$message['returned'] = isset($_POST['mn-reject-draft']);
			$message['notified'] = isset($_POST['mn-reject-email']);
			$message['rationale'] = wp_strip_all_tags(strtok($reject_rationale, ".\r\n"));
		} else if ($postarr['post_type'] === 'page') {
			// always audit page actions
			$action = 'page.' . ($update && $post->post_status !== 'auto-draft' ? 'update' : 'create');
		} else if ($cur_user == $postarr['post_author']) {
			// ignore regular editing actions taken by the post's author
			// non-strict comparison since WP_Post::post_author is a string
			return $data;
		}

		// parse changes
		$message['deltas'] = [];

		if ($postarr['post_type'] === 'post') {
			// parse changes to categories
			$old_categories = $post->post_category ?? [];
			$new_categories = array_filter($postarr['post_category'] ?? []);
			$new_categories = empty($new_categories) ? [get_option('default_category')] : $new_categories;
			$new_categories = array_map('intval', $new_categories);
			$categories_kept = array_intersect($old_categories, $new_categories);
			$categories_removed = array_map(array(self::class, 'get_category_name'), array_diff($old_categories, $categories_kept));
			$categories_added = array_map(array(self::class, 'get_category_name'), array_diff($new_categories, $categories_kept));

			if (count($categories_removed) > 0 || count($categories_added) > 0) {
				$message['deltas']['categories'] = [
					'removed' => array_values($categories_removed),
					'added' => array_values($categories_added),
				];
			}

			// parse changes to tags
			$old_tags = $post->tags_input ?? [];
			$new_tags = $postarr['tax_input']['post_tag'] ?? [];
			$new_tags = array_map(array(self::class, 'get_tag_name'), $new_tags);

			if ($postarr['post_status'] === 'pending' && empty($new_tags)) {
				// re-add default tag to unify with article submission behaviour
				$new_tags[] = Utils::get_current_tag();
			}

			$tags_kept = array_intersect($old_tags, $new_tags);
			$tags_removed = array_diff($old_tags, $tags_kept);
			$tags_added = array_diff($new_tags, $tags_kept);

			if (count($tags_removed) > 0 || count($tags_added) > 0) {
				$message['deltas']['tags'] = [
					'removed' => array_values($tags_removed),
					'added' => array_values($tags_added),
				];
			}
		}

		// parse changes to post status
		if ($post->post_status !== $postarr['post_status']) {
			$message['deltas']['status'] = [
				'old' => $post->post_status,
				'new' => $postarr['post_status'],
			];
		}

		// parse changes to post content
		if ($post->post_content !== $postarr['post_content']) {
			$message['deltas']['content'] = true;
		}

		if (
			($action !== 'post.update' && $action !== 'page.update') ||
			count($message['deltas']) > 0
		) {
			// changes we cared about were made, write an entry
			$this->audit($action, $cur_user, $postarr['ID'], $message);
		}

		// since this hooks into a filter, return the original object
		return $data;
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
	 * Audit plugin deactivation.
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
	 * Audit site option changes
	 *
	 * @since 1.4.0
	 * @uses added_option
	 * @uses updated_option
	 */
	public function audit_settings_update(string $option, $old_value, $value = null) {
		// don't log transients
		if (substr($option, 0, 11) === '_transient_' || substr($option, 0, 16) === '_site_transient_') {
			return;
		}

		// don't log certain options
		$disallowed_options = array_fill_keys([
			'active_plugins',
			'admin_email_lifespan',
			'akismet_show_user_comments_approved',
			'akismet_spam_count',
			'auto_core_update_notified',
			'auto_plugin_theme_update_emails',
			'cron',
			'jp_cc_reviews_installed_on',
			'mn_core_version',
			'mn_core_db_version',
			'recently_activated',
			'recently_edited',
			'recovery_keys',
			'tuxedo_big_file_uploads_reviews_time',
			'uninstall_plugins',
			'wordpress_api_key',
			'wp_all_export_db_version',
		], 1);
		if (isset($disallowed_options[$option]) || substr($option, 0, 10) === 'theme_mods_' || substr($option, 0, 7) === 'widget_') {
			return;
		}

		if (get_current_user_id() === 0 || !current_user_can('manage_options')) {
			// probably a scheduled call. abort.
			return;
		}

		if (func_num_args() === 2) {
			// called by added_option with two arguments
			$value = $old_value;
			$old_value = null;
		}

		// ignore if this is an update of the current settings slug, that's already handled by cur_issue.update
		if ($option === Consts\CURRENT_ISSUE_SETTINGS_SLUG) {
			// however, we do want to log an event when it's changed for the first time, since that's not handled
			if (func_num_args() === 2) {
				$this->audit_without_target('cur_issue.update', get_current_user_id(), [
					'old_tag' => null,
					'new_tag' => Utils::get_current_tag($value),
				]);
			}
			return;
		}

		$value = json_encode($value);
		$old_value = json_encode($old_value);

		$this->audit_without_target('settings.update', get_current_user_id(), [
			'option' => $option,
			'new_value' => $value,
			'old_value' => $old_value,
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
		$message = ['user_login' => $user->user_login];

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
