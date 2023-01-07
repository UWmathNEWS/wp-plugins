<?php

/**
 * Fired during plugin deactivation
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews\WP\Core
 */

namespace Mathnews\WP\Core;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Mathnews\WP\Core
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Clears scheduled actions.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook('mn_audit_clear_entries');
	}

}
