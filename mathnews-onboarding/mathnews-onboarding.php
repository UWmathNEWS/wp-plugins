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
 * Version:           1.0.0
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
define( 'MATHNEWS_ONBOARDING_VERSION', '1.0.0' );

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
 * The onboarding plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mathnews-onboarding.php';

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

  require_once MATHNEWS_CORE_BASEDIR . 'load.php';
  load_consts();
  load_utils();

	$plugin = new Mathnews_Onboarding($core_basedir);
	$plugin->run();

}

add_action('mathnews-core:init', __NAMESPACE__ . '\\run_mathnews_onboarding', 10000);
