<?php

/**
 * Fired during plugin removal
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.4.0
 *
 * @package    Mathnews\WP\Core
 */

namespace Mathnews\WP\Core;

/**
 * Fired during plugin removal.
 *
 * This class defines all code necessary to run during the plugin's removal.
 *
 * @since      1.4.0
 * @package    Mathnews\WP\Core
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Uninstaller {
	/**
	 * Uninstall the mathNEWS Core plugin.
	 *
	 * @since 1.4.0
	 */
	static public function uninstall() {
		global $wpdb;

		// Remove created options
		delete_site_option('mn_core_db_version');

		// Remove created tables
		$wpdb->query('DROP TABLE mn_audit_log');
	}
}
