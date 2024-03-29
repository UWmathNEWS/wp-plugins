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

	/**
	 * Require a file.
	 *
	 * @since 1.1.0
	 * @param string $basedir The base directory relative to which the include should be done
	 * @param string $path    The path to the file to include
	 */
	static public function require($basedir, $path) {
		require_once plugin_dir_path($basedir) . $path;
	}

	/**
	 * Require a core include file. Used by dependent plugins.
	 *
	 * @since 1.1.0
	 * @param string $path    The path to the file to include
	 */
	static public function require_core($path) {
		self::require(__FILE__, $path);
	}

	/**
	 * Build HTML attributes
	 *
	 * @since 1.3.0
	 * @param array $arr An array of attribute keys to values
	 */
	static public function build_attrs_from_array(array $arr) {
		if (empty($arr)) { return ''; }

		$str = '';
		foreach ($arr as $key => $value) {
			if (!empty($value) && $value !== false) {
				$str .= ' ' . $key . '="' . esc_attr($value) . '"';
			}
		}
		return $str;
	}

	/**
	 * Get current issue tag
	 *
	 * @since 1.4.0
	 * @param array $cur_issue An array of the form [volume_num, issue_num]
	 */
	static public function get_current_tag(array $cur_issue = null) {
		if (is_null($cur_issue)) {
			$cur_issue = get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT);
		}
		return "v${cur_issue[0]}i${cur_issue[1]}";
	}
}
