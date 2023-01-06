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
 * WARNING: Code here may be executed in an untrusted context, by untrusted users. Do NOT rely on user input.
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
		if ($new_version === $old_version) return;

		$old_version = $old_version ?? '0.0.0';

		$new_version_major = strtok($new_version, '.');
		$new_version_minor = strtok('.');
		$new_version_patch = strtok('.-');
		$new_version_suffix = strtok('');
		$old_version_major = strtok($old_version, '.');
		$old_version_minor = strtok('.');
		$old_version_patch = strtok('.-');
		$old_version_suffix = strtok('');

		// nested map of major/minor/patch versions to upgrade function key
		$upgrades = [
			1 => [
				4 => [
					0 => '140',
				],
			],
		];

		// perform upgrades between the previous version and this one
		foreach ($upgrades as $i => $major_upgrades) {
			if ($i < $old_version_major) continue;
			foreach ($major_upgrades as $j => $minor_upgrades) {
				if ($i <= $old_version_major && $j < $old_version_minor) continue;
				foreach ($minor_upgrades as $k => $patch_upgrade) {
					if ($i <= $old_version_major && $j <= $old_version_minor) {
						if ($k < $old_version_patch || (empty($old_version_suffix) && empty($new_version_suffix))) continue;
					}

					$method_name = "upgrade_$patch_upgrade";
					self::$method_name($dry_run);

					if ($i >= $new_version_major && $j >= $new_version_minor && $k >= $new_version_patch) break;
				}
			}
		}

		if ($dry_run) return;

		// Update saved version
		update_site_option('mn_core_version', $new_version);
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
	 * Upgrade to plugin version 1.4.0.
	 *
	 * @since 1.4.0
	 */
	private static function upgrade_140(bool $dry_run = true) {
		global $wpdb;

		// Create a table for the audit log
		self::db_delta("CREATE TABLE {$wpdb->prefix}mn_audit_log (
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

		// Change post.update.status audit messages to post.update, from previous alphas
		$entries = $wpdb->get_results("SELECT log_id, log_message FROM {$wpdb->prefix}mn_audit_log WHERE log_action = 'post.update.status'");
		$result = [];

		foreach ($entries as $entry) {
			$message = json_decode($entry->log_message);
			$new_message = [
				'deltas' => [
					'status' => [
						'old' => $message->old_status,
						'new' => $message->new_status,
					],
				],
			];

			if ($dry_run) {
				$result[$entry->log_id] = $new_message;
			} else {
				$wpdb->query($wpdb->prepare(
					"UPDATE {$wpdb->prefix}mn_audit_log SET log_action = 'post.update', log_message = %s WHERE log_id = %d",
					json_encode($new_message), $entry->log_id
				));
			}
		}

		// Clean up obsolete option from previous alphas
		if (!$dry_run) {
			delete_site_option('mn_core_db_version');
		}

		if ($dry_run) {
			trigger_error(var_dump($result), E_USER_ERROR);
		}
	}
}
