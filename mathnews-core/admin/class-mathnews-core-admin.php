<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 * @since      1.4.0 Refactored to split functionality into different modules.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 */

namespace Mathnews\WP\Core\Admin;

use Mathnews\WP\Core;
use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;

/**
 * Core admin bootstrap functionality of the plugin.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Admin {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @since    1.4.0 Removed $plugin_name and $plugin_version parameters.
	 */
	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('admin_init', array($this, 'create_categories'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @uses admin_enqueue_scripts
	 */
	public function enqueue_styles() {
		wp_enqueue_style(Core\PLUGIN_NAME, plugin_dir_url(__FILE__) . 'css/mathnews-core-admin.css', [], Core\VERSION, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @uses admin_enqueue_scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(Core\PLUGIN_NAME, plugin_dir_url(__FILE__) . 'js/mathnews-core-admin.js',
			['jquery', 'wp-tinymce', 'media-upload', 'tags-box'], Core\VERSION, true);
		wp_localize_script(Core\PLUGIN_NAME, 'mn_core', [
			'ajaxurl'       => admin_url('admin-ajax.php'),
			'currentIssue'  => esc_html(Utils::get_current_tag()),
			'isCopyeditor'  => current_user_can('edit_others_posts'),
			'isAdmin'       => current_user_can('manage_options'),
			'currentScreen' => get_current_screen()->id,
			'categories'    => !current_user_can('manage_options') ? new \ArrayObject() : [
				'BACKISSUE_CAT_NAME' => get_cat_ID(Consts\BACKISSUE_CAT_NAME),
			],
		]);
	}

	/**
	 * Create custom category names
	 *
	 * @since 1.0.0
	 * @uses admin_init
	 */
	public function create_categories() {
		if (!term_exists(Consts\APPROVED_CAT_NAME, 'category')) {
			wp_create_category(Consts\APPROVED_CAT_NAME);
		}
		if (!term_exists(Consts\REJECTED_CAT_NAME, 'category')) {
			wp_create_category(Consts\REJECTED_CAT_NAME);
		}
		if (!term_exists(Consts\BACKISSUE_CAT_NAME, 'category')) {
			wp_create_category(Consts\BACKISSUE_CAT_NAME);
		}
	}
}
