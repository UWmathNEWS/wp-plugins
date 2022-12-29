<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 */

namespace Mathnews\WP\Core\Admin;

use Mathnews\WP\Core;
use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;

Utils::require_core('class-mathnews-core-display.php');

/**
 * Manage beta and A/B testing.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class UserTesting {
	public function __construct() {
		add_filter('the_author', array($this, 'filter_author_AB'));
		add_action('admin_notices', array($this, 'feedback_notice'));
		add_action('mathnews-core:add_settings', array($this, 'add_AB_settings'));
	}

	/**
	 * Display a marker beside the names of authors who are enroled in the AB test
	 *
	 * @since 1.0.0
	 */
	public function filter_author_AB(string $display_name): string {
		global $authordata;
		if (self::ab_tests_enabled() && current_user_can('manage_options') && !is_null($display_name) && self::is_B_user($authordata->ID)) {
			return $display_name . ' <em>(*)</em>';
		}

		return $display_name;
	}

	/**
	 * Display a feedback notice for AB users
	 *
	 * @since 1.0.0
	 */
	public function feedback_notice() {
		if (!self::is_B()) { return; }

		Core\Display::admin_notice('info', <<<MSG
<p>We&#39;re testing out some changes to the article submission interface. Let us know what you think by emailing <a href="mailto:mathnews@gmail.com">mathnews@gmail.com</a>!</p>
MSG);
	}

	/**
	 * Add section to enable/disable user testing settings
	 *
	 * @since 1.4.0
	 * @uses mathnews-core:add_settings
	 */
	public function add_AB_settings($settings) {
		$settings->add_section('user-testing', 'User testing', ['tab' => 'general'])
			->register(
				'mn_usertesting_enable',
				'Programs',
				'checkbox',
				['off', 'off'],
				[
					'labels' => ['Enable self-enrolled beta testing program', 'Enable A/B testing program'],
				]
			);
	}

	/**
	 * Determine if AB tests are enabled.
	 *
	 * @since 1.4.0
	 */
	static public function ab_tests_enabled(): bool {
		return get_option('mn_usertesting_enable', ['off', 'off'])[1] === 'on';
	}

	/**
	 * Determine if AB test is on for a user. Returns true if plugin should be enabled for user.
	 *
	 * @param The user to test.
	 *
	 * @since 1.0.0
	 */
	static public function is_B_user($user_id): bool {
		return self::ab_tests_enabled() && (user_can($user_id, 'edit_others_posts') || ($user_id % 2 === 0));
	}

	/**
	 * Determine if AB test is on for the current user.
	 *
	 * @since 1.0.0
	 */
	static public function is_B(): bool {
		return self::is_B_user(get_current_user_id());
	}
}
