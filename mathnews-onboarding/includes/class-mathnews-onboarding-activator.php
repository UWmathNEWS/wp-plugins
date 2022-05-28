<?php

/**
 * Fired during plugin activation
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews\WP\Onboarding
 */

namespace Mathnews\WP\Onboarding;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Mathnews\WP\Onboarding
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Activator {

	/**
	 * Check activation conditions
	 *
	 * @since    1.0.1
	 */
	public static function activate() {
		if (!is_plugin_active('mathnews-core/mathnews-core.php')) {
			die('mathNEWS Onboarding not activated: mathNEWS Core either not installed or is inactive.');
		}

		$plugins_list = get_plugins();
		$mathnews_core_info = $plugins_list['mathnews-core/mathnews-core.php'];

		$core_version_info = explode('.', $mathnews_core_info['Version']);
		$min_core_version_info = explode('.', MIN_MATHNEWS_CORE);

		if (
			$core_version_info[0] < $min_core_version_info[0] ||
			($core_version_info[0] <= $min_core_version_info[0] && $core_version_info[1] < $min_core_version_info[1]) ||
			($core_version_info[0] <= $min_core_version_info[0] && $core_version_info[1] <= $min_core_version_info[1] && $core_version_info[2] < $min_core_version_info[2])
		) {
			die('mathNEWS Onboarding not activated: requires mathNEWS Core version ' . MIN_MATHNEWS_CORE . ', but found version ' . $mathnews_core_info['Version']);
		}
	}

}
