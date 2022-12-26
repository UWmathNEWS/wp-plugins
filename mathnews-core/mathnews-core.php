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
 * @package           Mathnews\WP\Core
 *
 * @wordpress-plugin
 * Plugin Name:       mathNEWS Core
 * Plugin URI:        mathnews.uwaterloo.ca
 * Description:       Revamp article submission workflow
 * Version:           1.3.0
 * Author:            mathNEWS Editors
 * Author URI:        All licensing queries should be directed to mathnews@gmail.com
 * License:           AGPL-3.0
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.en.html
 * Text Domain:       mathnews-core
 * Domain Path:       /languages
 */

namespace Mathnews\WP\Core;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
const VERSION = '1.3.0';
define( 'MATHNEWS_CORE_VERSION', VERSION );  // legacy API

require_once plugin_dir_path(__FILE__) . 'load.php';
load_consts();
load_utils();

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mathnews-core-activator.php
 */
function activate_mathnews_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mathnews-core-activator.php';
	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mathnews-core-deactivator.php
 */
function deactivate_mathnews_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mathnews-core-deactivator.php';
	Deactivator::deactivate();
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate_mathnews_core' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate_mathnews_core' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mathnews-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_mathnews_core() {

	add_action('plugins_loaded', function() {
		/**
		 * Allow dependent plugins to load
		 *
		 * @since 1.0.0
		 */
		do_action('mathnews-core:init', plugin_dir_path(__FILE__));
	});

	$plugin = new Mathnews_Core();
	$plugin->run();

}
run_mathnews_core();
