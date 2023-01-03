<?php

/**
 * Fired during plugin activation
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews\WP\Core
 */

namespace Mathnews\WP\Core;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Mathnews\WP\Core
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * Performs upgrades and initializes scheduled actions.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::upgrade(VERSION, get_site_option('mn_core_version'), false);
		self::upgrade_db(DB_VERSION, get_site_option('mn_core_db_version'), false);

		self::init_schedulers();
	}

	/**
	 * Register a scheduled event to clear old log entries.
	 *
	 * This event runs daily, though the duration to keep old logs for is set as an option.
	 *
	 * @since 1.4.0
	 */
	private static function init_schedulers() {
		$scheduled_events = [
			'mn_audit_clear_entries' => ['timestamp' => time(), 'recurrence' => 'daily'],
		];

		foreach ($scheduled_events as $event => $args) {
			if (!wp_next_scheduled($event)) {
				$success = wp_schedule_event($args['timestamp'], $args['recurrence'], $event, $args['args'] ?? []);

				if (!$success) {
					trigger_error("Event $event failed to be scheduled.", E_USER_ERROR);
				}
			}
		}
	}

	/**
	 * Upgrade from one version to another.
	 *
	 * @param string $new_version The version we are upgrading to
	 * @param string $old_version The version we are upgrading from
	 * @param bool $dry_run Perform a dry run of the upgrade, without actually doing anything
	 *
	 * @since 1.4.0
	 */
	private static function upgrade(string $new_version, string $old_version, bool $dry_run = true) {
		if ($dry_run) return;

		// Update saved version
		if (get_site_option('mn_core_version') === null) {
			add_site_option('mn_core_version', $new_version);
		} else {
			update_site_option('mn_core_version', $new_version);
		}
	}

	/**
	 * Upgrade from one DB version to another.
	 *
	 * @param string $new_version The version we are upgrading to
	 * @param string $old_version The version we are upgrading from
	 * @param bool $dry_run Perform a dry run of the upgrade, without actually doing anything
	 *
	 * @since 1.4.0
	 */
	private static function upgrade_db(int $new_version, int $old_version, bool $dry_run = true) {
		if ($new_version === $old_version) return;

		$old_version = intval($old_version);
		$new_version = intval($new_version);

		// Iteratively call upgrade functions
		for ($i = $old_version + 1; $i <= $new_version; $i++) {
			$method_name = "upgrade_db_$i";

			self::$method_name($dry_run);
		}

		if ($dry_run) return;

		// Update saved DB version
		if (get_site_option('mn_core_db_version') === null) {
			add_site_option('mn_core_db_version', $new_version);
		} else {
			update_site_option('mn_core_db_version', $new_version);
		}
	}

	/**
	 * Wrapper around dbDelta.
	 *
	 * @param string $query The query to pass to dbDelta
	 * @param bool $dry_run Don't execute the query, and output the operations performed
	 *
	 * @since 1.4.0
	 */
	private static function db_delta(string $query, bool $dry_run = true) {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Include dbDelta
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$result = dbDelta(str_replace('%charset%', $charset_collate, $query), !$dry_run);

		if ($dry_run) {
			trigger_error(var_dump($result), E_USER_ERROR);
		}
	}

	/**
	 * Create a table for the audit log.
	 *
	 * @since 1.4.0
	 */
	private static function upgrade_db_1(bool $dry_run = true) {
		self::db_delta("CREATE TABLE mn_audit_log (
			log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			log_time datetime NOT NULL,
			log_action varchar(40) NOT NULL,
			log_actor_id bigint(20) unsigned NOT NULL,
			log_target_id bigint(20) unsigned,
			log_message text NOT NULL,
			PRIMARY KEY  (log_id),
			KEY log_time (log_time),
			KEY log_action (log_action),
			KEY log_actor_id (log_actor_id),
			KEY log_target_id (log_target_id)
		) %charset%;", $dry_run);
	}
}
