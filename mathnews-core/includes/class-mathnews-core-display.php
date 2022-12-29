<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews_Core
 * @subpackage Mathnews_Core/admin/partials
 */

namespace Mathnews\WP\Core;

use Mathnews\WP\Core\Consts;

class Display {
	/**
	 * Generic helper for rendering notification dialogs
	 *
	 * @param $id ID to assign to the notification dialog
	 * @param $title Title of the notification dialog
	 * @param $callback Callback to render the notification dialog content
     * @param $hidden Should dialog be hidden?
	 * @param $args Additional arguments to pass to the callback function
	 *
	 * @since 1.0.0
	 */
	static public function notification_dialog($id, $title, $callback, $hidden = true, ...$args) {
		$safe_id = esc_attr($id);
		?>
<div id="<?php echo $safe_id; ?>" class="notification-dialog-wrap <?php echo ($hidden ? 'hidden' : ''); ?>">
    <div class="notification-dialog-background"></div>
    <div class="notification-dialog">
        <div id="<?php echo $safe_id; ?>--content" class="notification-dialog-content">
            <?php
            if ($title !== ''):
            ?>
                <h1 id="<?php echo $safe_id; ?>--title"><?php echo esc_html($title); ?></h1>
            <?php
            endif;
            ?>
            <?php call_user_func_array($callback, $args); ?>
        </div>
    </div>
</div>
		<?php
	}

	/**
	 * Renders an admin notice.
	 *
	 * @param string $type The type of notice to output. Must be one of `error`, `warning`, `success`, or `info`.
	 * @param string $content The HTML content of the notice.
	 * @param bool $is_dismissible If the notice can be dismissed by the user.
	 *
	 * @since 1.3.0
	 * @uses admin_notices
	 */
	static public function admin_notice(string $type, string $content, bool $is_dismissible = false) {
		if (!in_array($type, ['error', 'warning', 'success', 'info'])) { return; }

		echo sprintf('<div class="notice notice-%s %s">%s</div>',
			$type, $is_dismissible ? 'is-dismissible' : '', $content);
	}
}
