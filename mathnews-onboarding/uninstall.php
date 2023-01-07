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
 * @package    Mathnews\WP\Onboarding
 */

namespace Mathnews\WP\Onboarding;

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option('mn_temp_modal_show');
delete_option('mn_temp_modal_title');
delete_option('mn_temp_modal_text');
delete_option('mn_temp_modal_attachTo');
delete_option('mn_temp_modal_buttonText');
delete_option('mn_temp_modal_autohide');
delete_option('mn_temp_modal_show');
delete_option('mn_helpful_links_show_onboarding');
