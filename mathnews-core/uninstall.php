<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews_Core
 */

namespace Mathnews\WP\Core;

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . 'includes/mathnews-core-consts.php';

global $wpdb;

// Remove created options
delete_site_option('mn_core_version');

delete_option(Consts\CURRENT_ISSUE_OPTION_NAME);
delete_option(Consts\HELPFUL_LINKS_OPTION_NAME);
delete_option('mn_admin_notice_text');
delete_option('mn_admin_notice_type');
delete_option('mn_audit_persist_days');
delete_option('mn_email_smtp_use');
delete_option('mn_email_smtp_config__from');
delete_option('mn_email_smtp_config__host');
delete_option('mn_email_smtp_config__password');
delete_option('mn_email_smtp_config__port');
delete_option('mn_email_smtp_config__username');
delete_option('mn_usertesting_enable');

// Remove created tables
$wpdb->query("DROP TABLE {$wpdb->prefix}mn_audit_log");
