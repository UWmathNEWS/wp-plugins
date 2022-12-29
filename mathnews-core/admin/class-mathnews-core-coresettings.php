<?php
/**
 * Core plugin settings screen.
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

Utils::require_core('class-mathnews-core-settings.php');

/**
 * Core plugin settings screen.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class CoreSettings {
	/**
	 * General settings class
	 *
	 * @since    1.3.0
	 */
	private $settings;

	public function __construct() {
		$this->settings = new Core\Settings(Consts\CORE_SETTINGS_SLUG, __('mathNEWS Settings', 'textdomain'), true);

		add_action('admin_menu', array($this->settings, 'render_screen'), 10, 0);
		add_action('admin_init', array($this, 'init_settings'));
	}

	/**
	 * Register settings options
	 *
	 * @since 1.2.0
	 * @uses admin_init
	 */
	public function init_settings() {
		$this->settings
			->add_tab('general', 'General')
			->add_tab('writing', 'Writing');

		/**
		 * Allow for registration of additional settings to the mathNEWS settings page.
		 *
		 * @param Settings $settings The Settings object for the mathNEWS settings page.
		 *
		 * @since 1.3.0
		 */
		do_action('mathnews-core:add_settings', $this->settings);

		$this->settings->run();
	}
}
