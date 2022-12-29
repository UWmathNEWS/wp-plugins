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

/**
 * Email functionality for the plugin.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Email {
	public function __construct() {
		add_action('phpmailer_init', array($this, 'phpmailer_init'));
		add_action('wp_mail_failed', array($this, 'phpmailer_error_handler'));

		add_action('mathnews-core:add_settings', array($this, 'register_email_settings'));
	}

	/**
	 * Set PHPMailer config
	 *
	 * @since 1.3.0
	 * @uses phpmailer_init
	 */
	public function phpmailer_init($mailer) {
		if (get_option('mn_email_smtp_use', ['off'])[0] === 'on') {
			$mailer->isSMTP();
			$mailer->Host = get_option('mn_email_smtp_config__host', '');
			$mailer->Port = get_option('mn_email_smtp_config__port', '');
			$mailer->Username = get_option('mn_email_smtp_config__username', '');
			$mailer->Password = get_option('mn_email_smtp_config__password', '');
			$mailer->setFrom(get_option('mn_email_smtp_config__from', ''));
			$mailer->SMTPDebug = 1;
			$mailer->SMTPAuth = true;
			$mailer->SMTPSecure = 'tls';
			$mailer->CharSet = 'utf-8';
		}
	}

	/**
	 * Catch PHPMailer errors
	 *
	 * @since 1.3.0
	 * @uses wp_mail_failed
	 */
	public function phpmailer_error_handler($error) {
		global $phpmailer;
		wp_die($error);
	}

	/**
	 * Register email settings
	 * 
	 * @since 1.4.0
	 * @uses mathnews-core:add_settings
	 */
	public function register_email_settings($settings) {
		$settings
			->add_tab('advanced', 'Advanced')
			->add_section('email', 'Email', ['tab' => 'advanced'])
			->register('mn_email_smtp_use', __('External SMTP', 'textdomain'), 'checkbox', ['off'], [
				'labels' => ['Use external SMTP server'],
				'attrs' => [['id' => 'mn-use-smtp']],
			])
			->register('mn_email_smtp_config__host', __('SMTP server', 'textdomain'), 'text', 'smtp.example.com', [
				'attrs' => ['data-disabled-by' => '#mn-use-smtp::off'],
			])
			->register('mn_email_smtp_config__port', __('Port', 'textdomain'), 'text', '587', [
				'attrs' => ['data-disabled-by' => '#mn-use-smtp::off'],
			])
			->register('mn_email_smtp_config__username', __('Username', 'textdomain'), 'text', 'noreply@localhost', [
				'attrs' => ['data-disabled-by' => '#mn-use-smtp::off'],
			])
			->register('mn_email_smtp_config__password', __('Password', 'textdomain'), 'text', '', [
				'type'  => 'password',
				'attrs' => ['data-disabled-by' => '#mn-use-smtp::off'],
			])
			->register('mn_email_smtp_config__from', __('From address', 'textdomain'), 'text', 'noreply@localhost', [
				'attrs' => ['data-disabled-by' => '#mn-use-smtp::off']
			]);
	}
}
