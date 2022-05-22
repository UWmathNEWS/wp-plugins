<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews_Core
 * @subpackage Mathnews_Core/public
 */

namespace Mathnews\WP\Core\Public_;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Mathnews_Core
 * @subpackage Mathnews_Core/public
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Mathnews_Core_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * Unused since there are no public-facing elements (yet)
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mathnews_Core_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mathnews_Core_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mathnews-core-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * Unused since there are no public-facing elements (yet)
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mathnews_Core_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mathnews_Core_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mathnews-core-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * https://stackoverflow.com/a/16398370
	 *
	 * Unused for now.
	 *
	 * @since 1.0.0
	 */
	public function load_issues_on_front_page() {
		global $wp_query;
		// Compare queried page ID to front page ID.
		if (!is_admin() && $wp_query->get("page_id") == get_option("page_on_front")) {

				// Set custom parameters
				$wp_query->set("post_type", "mn-issue");

		}
	}

}
