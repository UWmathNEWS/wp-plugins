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

use Mathnews\WP\Core\Consts;

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
	 * Determine if the current post is an article or not.
	 *
	 * @since 1.2.0
	 */
	private function is_article($post_id = false) {
		$is_article = 0 === count(array_filter(get_the_category($post_id), function($term) {
			return $term->name === Consts\BACKISSUE_CAT_NAME;
		}));
	}

	/**
	 * Determine if the current post is a valid preview or not.
	 *
	 * @since 1.2.0
	 */
	private function is_preview() {
		return is_singular() && is_preview() && current_user_can('edit_post', get_the_ID());
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

	function show_post_subtitle($title) {
		$is_article = 0 === count(array_filter(get_the_category(), function($term) {
			return $term->name === Consts\BACKISSUE_CAT_NAME;
		}));
		if (is_preview() && in_the_loop() && is_main_query() && $is_article) {
			$post_id = get_the_ID();
			$subtitle = get_post_meta($post_id, Consts\SUBTITLE_META_KEY_NAME, true);
			if ($subtitle !== '') {
				// abuse the fact that headings cannot be nested in each other
				return $title . '<h3 class="entry-subtitle">' . esc_html($subtitle) . '</h3>';
			}
		}

		return $title;
	}

	function show_post_meta($content) {
		$is_article = 0 === count(array_filter(get_the_category(), function($term) {
			return $term->name === Consts\BACKISSUE_CAT_NAME;
		}));
		if (is_preview() && in_the_loop() && is_main_query() && $is_article) {
			$post_id = get_the_ID();
			$new_content = $content;
			$author = get_post_meta($post_id, Consts\AUTHOR_META_KEY_NAME, true);
			$postscript = get_post_meta($post_id, Consts\POSTSCRIPT_META_KEY_NAME, true);

			$new_content .= '<address class="entry-pseudonym">' . esc_html($author) . '</address>';

			if ($postscript !== '') {
				// no esc_html because people can and do put HTML in the postscript
				$new_content .= '<footer class="entry-postscript">' . $postscript . '</footer>';
			}

			return $new_content;
		}

		return $content;
	}

}
