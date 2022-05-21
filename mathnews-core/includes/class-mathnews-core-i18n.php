<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews_Core
 * @subpackage Mathnews_Core/includes
 */

namespace Ca\Mathnews\WP\Core;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Mathnews_Core
 * @subpackage Mathnews_Core/includes
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Mathnews_Core_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'mathnews-core',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
