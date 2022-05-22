<?php
/**
 * Shared utilities across plugins
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews\WP\Core
 */

namespace Mathnews\WP\Core;

use Mathnews\WP\Core\Consts;

require_once plugin_dir_path(__FILE__) . 'mathnews-core-consts.php';

class Utils {
	/**
	 * Determine if a user can edit a given post
	 *
	 * @since 1.0.0
	 */
	static public function can_edit($post) {
		return current_user_can('edit_others_posts') || ($post->post_status !== 'pending' && !has_category(Consts\APPROVED_CAT_NAME, $post));
	}
}
