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

Utils::require(__FILE__, 'partials/class-mathnews-core-postui.php');

/**
 * UI elements for the post page (e.g. publish meta box, helpful links, etc.)
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class PostUI {
	public function __construct() {
		add_action('mathnews-core:add_settings', array($this, 'register_writing_settings'));

		// register meta boxes and additional fields
		add_action('load-post.php', array($this, 'meta_box_setup'));
		add_action('load-post-new.php', array($this, 'meta_box_setup'));
	}

	/**
	 * Register settings for the post UI.
	 *
	 * @since 1.4.0
	 * @uses mathnews-core:add_settings
	 */
	public function register_writing_settings($settings) {
		$settings->add_section('writing', 'Helpful links', ['tab' => 'writing'])
			->register(
				Consts\HELPFUL_LINKS_OPTION_NAME,
				__('Displayed links', 'textdomain'),
				'textarea',
				'',
				[
					'description' => 'Shown in the post writing screen. One link per line, in the format <code>URL link_title</code>.',
					'attrs' => ['rows' => 5],
				]
			);
	}

	/**
	 * Setup meta box.
	 *
	 * @since 1.0.0
	 * @uses load-post.php
	 * @uses load-post-new.php
	 */
	public function meta_box_setup() {
		add_action('add_meta_boxes', array($this, 'remove_extraneous_meta_boxes'));
		add_action('add_meta_boxes', array($this, 'remove_categories_meta_box'));
		add_action('add_meta_boxes', array($this, 'create_publish_meta_boxes'));
	}

	/**
	 * Remove format, excerpt, trackbacks, discussion, slug, and custom fields meta boxes
	 *
	 * @since 1.0.0
	 * @uses add_meta_boxes
	 */
	public function remove_extraneous_meta_boxes() {
		remove_meta_box('formatdiv', 'post', 'side');
		remove_meta_box('postimagediv', 'post', 'side');

		remove_meta_box('postexcerpt', 'post', 'normal');
		remove_meta_box('trackbacksdiv', 'post', 'normal');
		remove_meta_box('commentstatusdiv', 'post', 'normal');
		remove_meta_box('slugdiv', 'post', 'normal');
		remove_meta_box('postcustom', 'post', 'normal');
		remove_meta_box('authordiv', 'post', 'normal');
	}

	/**
	 * Remove categories meta box for non-editors
	 *
	 * @since 1.0.0
	 * @uses add_meta_boxes
	 */
	public function remove_categories_meta_box() {
		if (!current_user_can('edit_others_posts')) {
			remove_meta_box('categorydiv', 'post', 'side');
		}
	}

	/**
	 * Create meta boxes related to mathNEWS custom submission flow
	 *
	 * @since 1.0.0
	 * @uses add_meta_boxes
	 */
	public function create_publish_meta_boxes() {
		global $post;  // i hate this but it's the easiest way to get the current post

		$post_id = $post->ID;
		$existing_cats = get_the_category($post_id);
		$post_to_be_published = 0 < count(array_filter($existing_cats, function($term) {
			return $term->name === Consts\BACKISSUE_CAT_NAME;
		}));
		$screen_is_publish = current_user_can('manage_options') && isset($_GET) && isset($_GET['mn-publish']);

		// only apply to posts that aren't backissue posts
		if (get_post_type() === Consts\POST_TYPE && !$post_to_be_published && !$screen_is_publish) {
			// replace publish meta box
			remove_meta_box('submitdiv', 'post', 'side');
			add_meta_box('mn-submitdiv', __('Submit', 'textdomain'), array($this, 'render_publish_meta_box'), 'post', 'side', 'high');

			// add subtitle input
			add_action('edit_form_before_permalink', array($this, 'add_subtitle_input'));

			// add writer's guide notice box
			add_meta_box('mn-helpful-links', __('Helpful Links', 'textdomain'), array($this, 'render_helpful_links_meta_box'), 'post', 'side', 'high');

			// add postscript meta box
			$postscript_meta_box_id = 'mn-postscriptdiv';
			add_meta_box($postscript_meta_box_id, __('Postscript', 'textdomain'), array($this, 'render_postscript_meta_box'), 'post', 'normal', 'high');
			add_filter("postbox_classes_post_${postscript_meta_box_id}", array($this, 'close_postscript_meta_box_by_default'));
		}
	}

	/**
	 * Wrapper for rendering the publish meta box
	 *
	 * @since 1.0.0
	 */
	public function render_publish_meta_box($post) {
		$nonce_field = wp_nonce_field(dirname(__FILE__), 'mn-submit-nonce', true, false);
		Partials\PostUI::render_publish_meta_box($post, $nonce_field, Utils::can_edit($post));
	}

	/**
	 * Add subtitle text input
	 *
	 * @since 1.0.0
	 * @uses edit_form_before_permalink
	 */
	public function add_subtitle_input($post) {
		$subtitle = get_post_meta($post->ID, Consts\SUBTITLE_META_KEY_NAME, true);

		Partials\PostUI::subtitle_input($subtitle, Utils::can_edit($post));
	}

	/**
	 * Wrapper for rendering the helpful links meta box
	 *
	 * @since 1.2.0
	 */
	public function render_helpful_links_meta_box($post) {
		Partials\PostUI::render_helpful_links_meta_box();
	}

	/**
	 * Render the postscript meta box
	 *
	 * @since 1.0.0
	 */
	public function render_postscript_meta_box($post) {
		$settings = [
			'media_buttons' => false,
			'textarea_rows' => 5,
			'teeny' => true,
		];
		wp_editor(get_post_meta($post->ID, Consts\POSTSCRIPT_META_KEY_NAME, true), 'mn-postscript', $settings);
	}

	/**
	 * Hide postscript meta box if not an editor and there's no postscript
	 *
	 * @since 1.0.0
	 * @uses postbox_classes_post_mn-postscriptdiv
	 */
	public function close_postscript_meta_box_by_default($classes) {
		global $post;

		if (!current_user_can('edit_others_posts') && get_post_meta($post->ID, Consts\POSTSCRIPT_META_KEY_NAME, true) === '') {
			$classes[] = 'closed';
		}

		return $classes;
	}
}
