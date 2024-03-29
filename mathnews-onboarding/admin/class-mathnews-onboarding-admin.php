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
	 * Onboarding option key name
	 *
	 * @since 1.1.1
	 */
	const ONBOARDING_OPTION_KEY_NAME = 'mn_onboarded_successfully';

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

		return Utils::can_edit($post) && get_user_option(self::ONBOARDING_OPTION_KEY_NAME) !== $this->current_onboarding_value();
	}

	/**
	 * Enqueue onboarding styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_onboarding_styles() {
		wp_enqueue_style( 'shepherd', plugin_dir_url( __FILE__ ) . 'css/vendor/shepherd.css', array(), '9.1.0', 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mathnews-onboarding.css', array(), $this->version, 'all' );
	}

	/**
	 * Enqueue onboarding scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_onboarding_scripts() {
		$nonce = wp_create_nonce('mn_onboarding');
		$temp_tour = time() < strtotime(get_option('mn_temp_modal_autohide', ''))
			? [
					'show'       => get_option('mn_temp_modal_show', ['off', 'on'])[1] !== 'on' || !$this->show_onboarding(),
					'title'      => get_option('mn_temp_modal_title', ''),
					'text'       => get_option('mn_temp_modal_text', ''),
					'buttonText' => get_option('mn_temp_modal_buttonText', 'Got it!'),
					'attachTo'   => [
						'element' => get_option('mn_temp_modal_attachTo', ''),
						'on'      => 'auto',
					],
				]
			: null;

		wp_enqueue_script( 'shepherd', plugin_dir_url( __FILE__ ) . 'js/vendor/shepherd.min.js', [], '9.1.0', true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mathnews-onboarding.js',
			['shepherd', 'jquery', 'mathnews-core'], $this->version, true );
		wp_localize_script($this->plugin_name, 'mn_onboarding', [
			'showOnboarding'   => $this->show_onboarding(),
			'nonce'            => $nonce,
			'temporaryTour'    => get_option('mn_temp_modal_show', ['off', 'on'])[0] === 'on' ? $temp_tour : null,
		]);
	}

	/**
	 * Set up an AJAX endpoint to mark a user as having completed onboarding
	 *
	 * @since 1.0.0
	 */
	public function mark_onboarding_completed() {
		check_ajax_referer('mn_onboarding');

		if (update_user_option(get_current_user_id(), self::ONBOARDING_OPTION_KEY_NAME, $this->current_onboarding_value())) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	public function temp_modal_settings($settings) {
		$s = $settings->add_section('temp-modal', __('Temporary modal', 'textdomain'), [
			'tab' => 'writing',
			'callback' => function() { echo 'Use this to highlight new changes and updates to the writing screen.'; },
		]);

		$s->register('mn_temp_modal_show', __('Visibility', 'textdomain'), 'checkbox', ['off', 'on'], [
				'labels' => ['Show temporary modal', 'Hide for new users'],
				'attrs' => [
					['id' => 'show-temp-modal'],
					['data-disabled-by' => '#show-temp-modal::off'],
				]
			])
			->register('mn_temp_modal_title', __('Title', 'textdomain'), 'text', 'Heads up!', [
				'attrs' => ['data-disabled-by' => '#show-temp-modal::off'],
			])
			->register('mn_temp_modal_text', __('Content', 'textdomain'), 'editor', 'This is an example modal message. Replace me as you wish!', [
				'editor' => ['textarea_rows' => 5, 'wpautop' => false],
				'attrs' => ['data-disabled-by' => '#show-temp-modal::off'],
			])
			->register('mn_temp_modal_attachTo', __('Selector', 'textdomain'), 'text', '#wp-content-wrap', [
				'description' => 'HTML selector of an element to highlight on-screen. Leave blank if you don\'t want to highlight anything.',
				'attrs' => ['data-disabled-by' => '#show-temp-modal::off'],
			])
			->register('mn_temp_modal_buttonText', __('Button text', 'textdomain'), 'text', 'Got it!', [
				'attrs' => ['data-disabled-by' => '#show-temp-modal::off'],
			])
			->register('mn_temp_modal_autohide', __('Hide after', 'textdomain'), 'text', date('Y-m-d', time()), [
				'type' => 'date',
				'attrs' => ['data-disabled-by' => '#show-temp-modal::off'],
			]);

		$settings->get('writing')->register('mn_helpful_links_show_onboarding', __('Additional helpful links', 'textdomain'), 'checkbox', ['on'], [
			'labels' => ['Show link to repeat onboarding tour'],
		]);
	}
}
