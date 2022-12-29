<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 */

namespace Mathnews\WP\Core\Admin;

use Mathnews\WP\Core;
use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;

Utils::require_core('class-mathnews-core-display.php');

/**
 * Display admin notices.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Notices {
	public function __construct() {
		add_action('admin_notices', array($this, 'admin_notice'));
		add_action('mathnews-core:add_settings', array($this, 'register_settings'));
		add_action('mn_settings_enqueue_scripts_' . Consts\CORE_SETTINGS_SLUG, array($this, 'enqueue_notice_scripts'));
	}

	/**
	 * Renders an admin notice
	 *
	 * @since 1.3.0
	 * @uses admin_notices
	 */
	public function admin_notice() {
		$notice_type = get_option('mn_admin_notice_type', 'none');
		$notice_text = get_option('mn_admin_notice_text', '');

		Core\Display::admin_notice($notice_type, $notice_text);
	}

	/**
	 * Registers the setting to set admin notices
	 *
	 * @since 1.4.0
	 * @uses mathnews-core:add_settings
	 */
	public function register_settings($settings) {
		$settings->add_section('admin-notice', 'Notice', [
			'tab' => 'general',
			'after_section' => '<div id="sample-notice"><h4>Notice preview:</h4><div></div></div>',
		])
			->register(
				'mn_admin_notice_type',
				__('Notice type', 'textdomain'),
				'select',
				'none',
				[
					'values' => ['none', 'error', 'warning', 'success', 'info'],
					'labels' => [
						'none' => 'Do not show',
						'error' => 'Error',
						'warning' => 'Warning',
						'success' => 'Success',
						'info' => 'Info'
					],
				]
			)
			->register(
				'mn_admin_notice_text',
				__('Message', 'textdomain'),
				'editor',
				'Hello! I\'m an example notice message, please replace me!',
				[
					'editor' => ['textarea_rows' => 5, 'wpautop' => false],
					'attrs'  => ['data-disabled-by' => '#mn_admin_notice_type-input::none']
				]
			);
	}

	/**
	 * Small inline script to display a preview notice in the settings page.
	 *
	 * @since 1.4.0
	 * @uses mn_settings_enqueue_scripts_{Consts\CORE_SETTINGS_SLUG}
	 */
	public function enqueue_notice_scripts() {
		wp_register_script('mn_core_notice_settings', '', ['wp-tinymce'], null, true);
		wp_enqueue_script('mn_core_notice_settings');
		wp_add_inline_script('mn_core_notice_settings', <<<'SCRIPT'
jQuery(($) => {
	const updateNotice = (text) => {
		const noticeType = $('#mn_admin_notice_type-input').val();
		if (noticeType !== "none") {
			$('#sample-notice').show();
			$('#sample-notice div').html(`<div class="notice notice-${noticeType} inline">${text}</div>`);
		} else {
			$('#sample-notice').hide();
		}
	};
	let scriptInitialized = false;
	$(document).on('tinymce-editor-init', () => {
		const editor = tinymce.get('mn_admin_notice_text-input');

		if (scriptInitialized || editor === null) return;

		editor.on('input', () => {
			updateNotice(editor.getContent());
		});
		scriptInitialized = true;
	});
	$('#mn_admin_notice_type-input, #mn_admin_notice_text-input').on('input', () => {
		tinymce.get('mn_admin_notice_text-input')?.save();
		updateNotice($('#mn_admin_notice_text-input').val());
	});

	updateNotice($('#mn_admin_notice_text-input').val());
});
SCRIPT);
	}
}
