<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews\WP\Core
 */

namespace Mathnews\WP\Core;

use Mathnews\WP\Core\Utils;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Mathnews\WP\Core
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Mathnews_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'MATHNEWS_CORE_VERSION' ) ) {
			$this->version = MATHNEWS_CORE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'mathnews-core';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Mathnews_Core_Loader. Orchestrates the hooks of the plugin.
	 * - Mathnews_Core_i18n. Defines internationalization functionality.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		Utils::require_core('class-mathnews-core-loader.php');

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		Utils::require_core('class-mathnews-core-i18n.php');

		$this->loader = new Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Mathnews_Core_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		if (is_admin()) {
			Utils::require(dirname( __FILE__ ), 'admin/class-mathnews-core-admin.php');

			$plugin_admin = new Admin\Mathnews_Core_Admin( $this->get_plugin_name(), $this->get_version() );

			// basic loading of scripts and styles
			$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
			$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

			// create categories needed
			$this->loader->add_action('admin_init', $plugin_admin, 'create_categories');

			// set phpmailer config
			$this->loader->add_action('phpmailer_init', $plugin_admin, 'phpmailer_init');
			$this->loader->add_action('wp_mail_failed', $plugin_admin, 'phpmailer_error_handler');

			// register settings screen
			$this->loader->add_action('admin_menu', $plugin_admin, 'add_settings_screen');
			$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
			$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_settings_scripts');

			// register screen to set the current issue
			$this->loader->add_action('admin_menu', $plugin_admin, 'add_current_issue_settings_screen');
			$this->loader->add_action('admin_init', $plugin_admin, 'register_current_issue_settings');
			$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_current_issue_settings_scripts');
			$this->loader->add_action('update_option_' . Consts\CURRENT_ISSUE_OPTION_NAME, $plugin_admin, 'move_current_issue_pending_to_draft');

			// remove quick draft widget from dashboard
			$this->loader->add_action('admin_init', $plugin_admin, 'remove_quick_draft_widget');

			// register meta boxes and additional fields
			$this->loader->add_action('load-post.php', $plugin_admin, 'meta_box_setup');
			$this->loader->add_action('load-post-new.php', $plugin_admin, 'meta_box_setup');

			// register additional input fields' corresponding action handlers
			$this->loader->add_action('save_post_post', $plugin_admin, 'save_subtitle_post_meta', 10, 2);
			$this->loader->add_action('save_post_post', $plugin_admin, 'save_author_post_meta', 10, 2);
			$this->loader->add_action('save_post_post', $plugin_admin, 'save_postscript_post_meta', 10, 2);

			// add the current issue tag and normalize categories on submit
			$this->loader->add_action('pending_post', $plugin_admin, 'add_current_issue_tag', 10, 2);
			$this->loader->add_action('pending_post', $plugin_admin, 'normalize_categories_on_submit', 10, 2);

			// add link to pending articles to sidebar
			$this->loader->add_action('admin_menu', $plugin_admin, 'link_to_pending');

			// colour categories for easy recognition
			$this->loader->add_filter('post_column_taxonomy_links', $plugin_admin, 'colourize_categories', 10, 3);

			// handlers for post approval and rejection
			$this->loader->add_action('save_post_post', $plugin_admin, 'handle_post_approval', 10, 2);
			$this->loader->add_filter('wp_insert_post_data', $plugin_admin, 'prepend_rejection_rationale');
			$this->loader->add_filter('wp_insert_post_data', $plugin_admin, 'normalize_post_status_on_approval');

			// lock editor after submission
			$this->loader->add_action('admin_footer-post.php', $plugin_admin, 'show_editor_lock_warning');
			$this->loader->add_filter('tiny_mce_before_init', $plugin_admin, 'lock_tinymce');
			$this->loader->add_filter('teeny_mce_before_init', $plugin_admin, 'lock_tinymce');

			// restrict contributors from quick-editing posts
			$this->loader->add_filter('quick_edit_show_taxonomy', $plugin_admin, 'remove_categories_from_quickedit', 10, 3);
			$this->loader->add_filter('post_row_actions', $plugin_admin, 'modify_post_row_actions', 10, 2);

			// Show pseudonym instead of display name
			$this->loader->add_filter('the_author', $plugin_admin, 'show_pseudonym_as_author');

			// AB tests
			$this->loader->add_filter('the_author', $plugin_admin, 'filter_author_AB');
			$this->loader->add_action('admin_notices', $plugin_admin, 'feedback_notice');

			// Show admin notice to all users
			$this->loader->add_action('admin_notices', $plugin_admin, 'admin_notice');
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		Utils::require(dirname( __FILE__ ), 'public/class-mathnews-core-public.php');

		$plugin_public = new Public_\Mathnews_Core_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_filter('the_title', $plugin_public, 'show_post_subtitle', 1);
		$this->loader->add_filter('the_content', $plugin_public, 'show_post_meta', 1);

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Mathnews_Core_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
