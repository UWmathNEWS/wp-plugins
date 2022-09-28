<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              All licensing queries should be directed to mathnews@gmail.com
 * @since             1.0.0
 * @package           Mathnews\WP\Onboarding
 *
 * @wordpress-plugin
 * Plugin Name:       mathNEWS Onboarding
 * Plugin URI:        mathnews.uwaterloo.ca
 * Description:       Onboard writers to the mathNEWS submission flow
 * Version:           1.0.2
 * Author:            mathNEWS Editors
 * Author URI:        All licensing queries should be directed to mathnews@gmail.com
 * License:           AGPL-3.0
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.en.html
 * Text Domain:       mathnews
 * Domain Path:       /languages
 */

namespace Mathnews\WP\Onboarding;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
const VERSION = '1.0.2';
define( 'MATHNEWS_ONBOARDING_VERSION', VERSION );  // legacy API

/**
 * Minimum version of mathNEWS Core required
 */
const MIN_MATHNEWS_CORE = '1.2.0';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mathnews-onboarding-activator.php
 */
function activate_mathnews_onboarding() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mathnews-onboarding-activator.php';
	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mathnews-onboarding-deactivator.php
 */
function deactivate_mathnews_onboarding() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mathnews-onboarding-deactivator.php';
	Deactivator::deactivate();
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate_mathnews_onboarding' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate_mathnews_onboarding' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_mathnews_onboarding() {
	/**
	 * Ensure minimum mathNEWS Core version before running
	 */
	$core_version_info = explode('.', \Mathnews\WP\Core\VERSION);
	$min_core_version_info = explode('.', MIN_MATHNEWS_CORE);

	if (
		$core_version_info[0] < $min_core_version_info[0] ||
		($core_version_info[0] <= $min_core_version_info[0] && $core_version_info[1] < $min_core_version_info[1]) ||
		($core_version_info[0] <= $min_core_version_info[0] && $core_version_info[1] <= $min_core_version_info[1] && $core_version_info[2] < $min_core_version_info[2])
	) {
		add_action('admin_notices', function() {
			// check eligibility in callback to avoid a DB call impacting frontend
			if (current_user_can('install_plugins')) {
				echo '<div class="notice notice-error"><p>mathNEWS Onboarding could not run: ';
				echo 'Requires mathNEWS Core version ' . MIN_MATHNEWS_CORE . ', but found version ' . \Mathnews\WP\Core\VERSION;
				echo '</p></div>';
			}
		});
		return;
	}

	require plugin_dir_path( __FILE__ ) . 'includes/class-mathnews-onboarding.php';

	$plugin = new Mathnews_Onboarding();
	$plugin->run();

}

add_action('mathnews-core:init', __NAMESPACE__ . '\\run_mathnews_onboarding', 10000);
