<?php

/**
 * Mixin that contains audit functionality
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.4.0
 *
 * @package    Mathnews\WP\Core
 */

namespace Mathnews\WP\Core;

use Mathnews\WP\Core\Consts;

/**
 * A mixin that extending classes can use to add entries to the audit log.
 *
 * @since 1.4.0
 * @package Mathnews\WP\Core
 * @author mathNEWS Editors <mathnews@gmail.com>
 */
trait Audit {
	/**
	 * The name of the DB table that stores audit logs.
	 *
	 * @since 1.4.0
	 * @access private
	 */
	private $audit_table = 'mn_audit_log';

	/**
	 * Add an entry to the audit log.
	 *
	 * @param string $action The type of action that was taken, of the form `<unit>.<verb>(.<suffix>)?`. Limited to 40
	 *                       characters.
	 * @param int $actor_id The ID of the user who performed the action.
	 * @param int $target_id The ID of the object that the action was performed on. For example, if the action taken was
	 *                       approving a post, this parameter would be passed the ID of the approved post.
	 * @param array $message Additional data associated with the action.
	 *
	 * @since 1.4.0
	 * @access private
	 */
	private function audit(string $action, int $actor_id, int $target_id, array $message) {
		global $wpdb;

		$result = $wpdb->query($wpdb->prepare(
			"INSERT INTO $this->audit_table (log_time, log_action, log_actor_id, log_target_id, log_message)
			VALUES (UTC_TIMESTAMP(), %s, %d, %d, %s)",
			[$action, $actor_id, $target_id, json_encode((object)$message, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK)]
		));
	}

	/**
	 * Add an entry to the audit log, without a target.
	 *
	 * This method is to be used when there is no specific target for the action, e.g. in the case of bulk actions.
	 *
	 * @param string $action The type of action that was taken, of the form `<unit>.<verb>(.<suffix>)?`. Limited to 40
	 *                       characters.
	 * @param int $actor_id The ID of the user who performed the action.
	 * @param array $message Additional data associated with the action.
	 *
	 * @since 1.4.0
	 * @access private
	 */
	private function audit_without_target(string $action, int $actor_id, array $message) {
		global $wpdb;

		return $wpdb->query($wpdb->prepare(
			"INSERT INTO $this->audit_table (log_time, log_action, log_actor_id, log_message)
			VALUES (UTC_TIMESTAMP(), %s, %d, %s)",
			[$action, $actor_id, json_encode((object)$message, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK)]
		));
	}
}
