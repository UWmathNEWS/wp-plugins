<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews\WP\Onboarding
 * @subpackage Mathnews\WP\Onboarding\Admin
 */

namespace Mathnews\WP\Onboarding\Admin;

use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Mathnews\WP\Onboarding
 * @subpackage Mathnews\WP\Onboarding\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Mathnews_Onboarding_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The major version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current major version of this plugin.
	 */
	private $major_version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->major_version = explode('.', $version, 2)[0];

	}

	/**
	 * Initialize onboarding stuff
	 *
	 * @since 1.0.0
	 */
	public function init_onboarding_scripts() {
		add_action('admin_enqueue_scripts', array($this, 'enqueue_onboarding_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_onboarding_scripts'));
	}

	/**
	 * Build onboarding option value for comparison
	 *
	 * @since 1.0.0
	 */
	private function current_onboarding_value() {
		return $this->major_version . ';' . wp_get_current_user()->roles[0];
	}

	/**
	 * Determine if onboarding modal should be shown
	 *
	 * @since 1.0.0
	 */
	private function show_onboarding() {
		global $post;

		return Utils::can_edit($post) && get_user_option(Consts\ONBOARDING_OPTION_KEY_NAME) !== $this->current_onboarding_value();
	}

	/**
	 * Enqueue onboarding styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_onboarding_styles() {
		if (!$this->show_onboarding()) {
			return;
		}

		wp_enqueue_style( 'shepherd', plugin_dir_url( __FILE__ ) . 'css/vendor/shepherd.css', array(), '9.1.0', 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mathnews-onboarding.css', array(), $this->version, 'all' );
	}

	/**
	 * Enqueue onboarding scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_onboarding_scripts() {
		if (!$this->show_onboarding()) {
			return;
		}

		$nonce = wp_create_nonce('mn_onboarding');

		wp_enqueue_script( 'shepherd', plugin_dir_url( __FILE__ ) . 'js/vendor/shepherd.min.js', [], '9.1.0', true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mathnews-onboarding.js',
			['shepherd', 'jquery', 'mathnews-core'], $this->version, true );
		wp_localize_script($this->plugin_name, 'mn_onboarding', [
			'nonce'        => $nonce,
		]);
	}

	/**
	 * Set up an AJAX endpoint to mark a user as having completed onboarding
	 *
	 * @since 1.0.0
	 */
	public function mark_onboarding_completed() {
		check_ajax_referer('mn_onboarding');

		if (update_user_option(get_current_user_id(), Consts\ONBOARDING_OPTION_KEY_NAME, $this->current_onboarding_value())) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}
}
