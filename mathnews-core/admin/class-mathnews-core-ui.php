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

/**
 * UI elements for the admin area
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class UI {
	public function __construct() {
		// remove quick draft widget from dashboard
		add_action('admin_init', array($this, 'remove_quick_draft_widget'));

		// add link to pending articles to sidebar
		add_action('admin_menu', array($this, 'link_to_pending'));

		// colour categories for easy recognition
		add_filter('post_column_taxonomy_links', array($this, 'colourize_categories'), 10, 3);

		// Show pseudonym instead of display name
		add_filter('the_author', array($this, 'show_pseudonym_as_author'));

		// restrict contributors from quick-editing posts
		add_filter('quick_edit_show_taxonomy', array($this, 'remove_categories_from_quickedit'), 10, 3);
		add_filter('post_row_actions', array($this, 'modify_post_row_actions'), 10, 2);

		// Link to user's posts in users list table
		add_filter('user_row_actions', array($this, 'modify_user_row_actions'), 10, 2);
	}

	/**
	 * Remove quick draft widget from dashboard, and add a meta box to display authors for this issue for editors.
	 *
	 * This is because WordPress will format posts created using this widget as if they were composed in the
	 * block editor. This introduces extra formatting that breaks our import scripts and also, at times, the
	 * Classic Editor.
	 *
	 * @since 1.2.0
	 * @uses admin_init
	 */
	public function remove_quick_draft_widget()	{
		remove_meta_box('dashboard_quick_press', 'dashboard', 'normal');
		if (current_user_can('manage_options')) {
			$cur_tag = Utils::get_current_tag();
			$title = sprintf(__('Authors for %s', 'textdomain'), $cur_tag);
			add_meta_box('mn-authors', $title, array(self::class, 'render_current_issue_authors_meta_box'), 'dashboard', 'normal');
		}
	}

	/**
	 * Add a link to the list of pending posts
	 *
	 * @since 1.1.1
	 * @uses admin_menu
	 */
	public function link_to_pending() {
		add_submenu_page('edit.php', __('Pending') . ' ' . __('Posts'), __('Pending') . ' ' . __('Posts'),
			'edit_others_posts',
			add_query_arg([
				'post_status' => 'pending',
				'post_type' => 'post',
				'orderby' => 'date',
				'order' => 'desc',
			], admin_url('edit.php')),
			'', 1);
	}

	/**
	 * Colourize categories and select tags so they're more visible
	 *
	 * @since 1.1.1
	 * @uses post_column_taxonomy_links
	 */
	public function colourize_categories($term_links, $taxonomy, $terms) {
		if (!current_user_can('edit_others_posts')) {
			return $term_links;
		}

		$n = count($terms);

		if ($taxonomy === 'category') {
			for ($i = 0; $i < $n; $i++) {
				if (in_array($terms[$i]->name, [Consts\APPROVED_CAT_NAME, 'Uncategorized'], true)) {
					$term_links[$i] = sprintf('<span class="taxonomy-pill category--%s">%s</span>', $terms[$i]->slug, $term_links[$i]);
					break;
				}
			}
		} elseif ($taxonomy === 'post_tag') {
			$default_tag = Utils::get_current_tag();
			for ($i = 0; $i < $n; $i++) {
				if ($terms[$i]->name === $default_tag) {
					$term_links[$i] = sprintf('<span class="taxonomy-pill tag--current-issue">%s</span>', $term_links[$i]);
				}
			}
		}

		return $term_links;
	}

	/**
	 * Show the pseudonym used on an article in the author column of the posts table
	 *
	 * @since 1.3.0
	 * @uses the_author
	 */
	public function show_pseudonym_as_author($display_name) {
		global $post;
		$nickname = get_post_meta($post->ID, Consts\AUTHOR_META_KEY_NAME, true) ?: $display_name;

		if (current_user_can('manage_options')) {
			$cur_tag = Utils::get_current_tag();
			$count = count(get_posts([
				'numberposts' => -1,
				'post_status' => 'any',
				'author' => $post->post_author,
				'tag' => $cur_tag,
			]));

			$html = esc_html($nickname);
			$html .= '<details>';
			// <a> here ensures that the <summary> is clickable
			$html .= '<summary><a><em>' . $display_name . ' (' . $count . ')</em></a></summary>';
			$html .= '<a href="' . get_edit_user_link($post->post_author) . '">Edit user profile</a>';
			$html .= '</details>';
			return $html;
		} elseif (current_user_can('edit_others_posts') || $post->post_author == get_current_user_id()) {
			return esc_html($nickname) . ' <em>(' . $display_name . ')</em>';
		}

		// user doesn't have editing privileges, so we only show the pseudonym.
		// echo here will output text without wrapping it in a link to all of the author's posts, to preserve privacy
		echo esc_html($nickname);
		return null;
	}

	/**
	 * Prevent categories from showing in quick edit for non-editors
	 *
	 * @since 1.0.0
	 * @uses quick_edit_show_taxonomy
	 */
	public function remove_categories_from_quickedit($show, $taxonomy_name, $post_type) {
		if (!current_user_can('edit_others_posts') && in_array($taxonomy_name, ['category', '_status'])) {
			return false;
		}

		return $show;
	}

	/**
	 * Prevent users from trashing articles they've submitted
	 *
	 * @since 1.0.0
	 * @uses post_row_actions
	 */
	public function modify_post_row_actions($actions, $post) {
		if (!Utils::can_edit($post)) {
			unset($actions['inline hide-if-no-js']);
			unset($actions['trash']);
		}

		return $actions;
	}

	/**
	 * Link to a user's posts in admin instead of their published posts (which shouldn't exist).
	 *
	 * @since 1.4.0
	 * @uses user_row_actions
	 */
	public function modify_user_row_actions($actions, $user) {
		$posts_url = esc_url(add_query_arg(['post_type' => 'post', 'author' => $user->ID], admin_url('edit.php')));
		$actions['view'] = "<a href='$posts_url'>View posts</a>";

		return $actions;
	}

	/**
	 * Renders a meta box containing all users who submitted posts to the current issue
	 *
	 * @since 1.3.0
	 */
	static public function render_current_issue_authors_meta_box() {
		$cur_tag = Utils::get_current_tag();
		$posts = get_posts([
			'numberposts' => -1,
			'post_status' => 'any',
			'tag' => $cur_tag,
		]);
		$article_count = [];

		foreach ($posts as $post) {
			$pseudonym = get_post_meta($post->ID, Consts\AUTHOR_META_KEY_NAME, true);
			$article_count[$pseudonym] = ($article_count[$pseudonym] ?? 0) + 1;
		}

		$pseudonyms = array_keys($article_count);
		natcasesort($pseudonyms);

		foreach ($pseudonyms as $pseudonym) {
			echo sprintf('<p><label><input type="checkbox"> %s (%s)</label></p>',
				esc_html($pseudonym), $article_count[$pseudonym]);
		}
	}
}
