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
 * Article submission flow.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class ArticleSubmission {
	public function __construct() {
		// register additional input fields' corresponding action handlers
		add_action('save_post_post', array($this, 'save_subtitle_post_meta'), 10, 2);
		add_action('save_post_post', array($this, 'save_author_post_meta'), 10, 2);
		add_action('save_post_post', array($this, 'save_postscript_post_meta'), 10, 2);

		// add the current issue tag and normalize categories on submit
		add_action('pending_post', array($this, 'add_current_issue_tag'), 10, 2);
		add_action('pending_post', array($this, 'normalize_categories_on_submit'), 10, 2);

		// lock editor after submission
		add_action('admin_footer-post.php', array($this, 'show_editor_lock_warning'));
		add_filter('tiny_mce_before_init', array($this, 'lock_tinymce'));
		add_filter('teeny_mce_before_init', array($this, 'lock_tinymce'));
	}

	/**
	 * Handle saving subtitle
	 *
	 * @since 1.0.0
	 * @uses save_post_post
	 */
	public function save_subtitle_post_meta($post_id, $post) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], dirname(__FILE__))) {
			return $post_id;
		}

		$post_type = $post->post_type;
		$post_type_object = get_post_type_object($post_type);
		$can_edit = current_user_can($post_type_object->cap->edit_post, $post_id);
		$new_subtitle = isset($_POST['mn_subtitle']) ? $_POST['mn_subtitle'] : '';

		if ($post_type !== Consts\POST_TYPE || !$can_edit) {
			return $post_id;
		}

		$current_subtitle = get_post_meta($post_id, Consts\SUBTITLE_META_KEY_NAME, true);

		if ($new_subtitle !== '' && $current_subtitle === '') {
			add_post_meta($post_id, Consts\SUBTITLE_META_KEY_NAME, $new_subtitle, true);
		} elseif ($new_subtitle !== '' && $new_subtitle !== $current_subtitle) {
			update_post_meta($post_id, Consts\SUBTITLE_META_KEY_NAME, $new_subtitle);
		} elseif ($new_subtitle === '' && $current_subtitle !== '') {
			delete_post_meta($post_id, Consts\SUBTITLE_META_KEY_NAME);
		}
	}

	/**
	 * Handle saving author
	 *
	 * @since 1.0.0
	 * @uses save_post_post
	 */
	public function save_author_post_meta($post_id, $post) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], dirname(__FILE__))) {
			return $post_id;
		}

		$post_type = $post->post_type;
		$post_type_object = get_post_type_object($post_type);
		$can_edit = current_user_can($post_type_object->cap->edit_post, $post_id);
		$new_author = isset($_POST['mn_author']) ? $_POST['mn_author'] : get_the_author_meta('nickname', $post->post_author);

		if ($post_type !== Consts\POST_TYPE || !$can_edit) {
			return $post_id;
		}

		$current_author = get_post_meta($post_id, Consts\AUTHOR_META_KEY_NAME, true);

		if ($new_author !== '' && $new_author !== $current_author) {
			update_post_meta($post_id, Consts\AUTHOR_META_KEY_NAME, $new_author);
		} elseif ($current_author === '') {
			add_post_meta($post_id, Consts\AUTHOR_META_KEY_NAME, $new_author, true);
		}
	}

	/**
	 * Handle saving postscript
	 *
	 * @since 1.0.0
	 * @uses save_post_post
	 */
	public function save_postscript_post_meta($post_id, $post) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], dirname(__FILE__))) {
			return $post_id;
		}

		$post_type = $post->post_type;
		$post_type_object = get_post_type_object($post_type);
		$can_edit = current_user_can($post_type_object->cap->edit_post, $post_id);
		$new_postscript = isset($_POST['mn-postscript']) ? trim($_POST['mn-postscript']) : '';

		if ($post_type !== Consts\POST_TYPE || !$can_edit) {
			return $post_id;
		}

		$current_postscript = get_post_meta($post_id, Consts\POSTSCRIPT_META_KEY_NAME, true);

		if ($new_postscript !== '' && $current_postscript === '') {
			add_post_meta($post_id, Consts\POSTSCRIPT_META_KEY_NAME, $new_postscript, true);
		} elseif ($new_postscript !== '' && $new_postscript !== $current_postscript) {
			update_post_meta($post_id, Consts\POSTSCRIPT_META_KEY_NAME, $new_postscript);
		} elseif ($new_postscript === '' && $current_postscript !== '') {
			delete_post_meta($post_id, Consts\POSTSCRIPT_META_KEY_NAME);
		}
	}

	/**
	 * Add current issue tag to articles that were submitted without a tag
	 *
	 * @since 1.0.0
	 * @uses pending_post
	 */
	public function add_current_issue_tag($post_id, $post) {
		$current_tags = get_the_tags($post_id);
		if ($current_tags === false) {
			$default_tag = Utils::get_current_tag();
			wp_set_post_tags($post_id, $default_tag);
		}
	}

	/**
	 * Normalize categories on submission. This is to ensure freshly submitted articles aren't marked with a decision.
	 *
	 * @since 1.2.2
	 * @uses pending_post
	 */
	public function normalize_categories_on_submit($post_id, $post) {
		if (get_current_user_id() == $post->post_author) {
			wp_set_post_categories($post_id, []);
		}
	}

	/**
	 * Lock post if status is pending
	 *
	 * @since 1.0.0
	 * @uses admin_footer-post.php
	 */
	public function show_editor_lock_warning() {
		global $post;

		if (Utils::can_edit($post)) {
			return;
		}

		Core\Display::notification_dialog('mn-editor-lock-warning', __('This post is locked for publishing', 'textdomain'),
			array(self::class, 'render_editor_lock_dialog'), false);
	}

	/**
	 * Lock TinyMCE
	 *
	 * @since 1.0.0
	 * @uses tiny_mce_before_init
	 * @uses teeny_mce_before_init
	 */
	public function lock_tinymce($mceInit) {
		global $post;

		if (!Utils::can_edit($post)) {
			$mceInit['readonly'] = true;
		}

		return $mceInit;
	}

	/**
	 * Renders the editor lock warning modal.
	 *
	 * @since 1.0.0
	 */
	static public function render_editor_lock_dialog() {
		?>
<div id="mn-editor-lock-warning--message">
    <p><?php _e('If you have any changes you would like to make, please contact the editors.', 'textdomain'); ?></p>
</div>
<p>
    <a class="button button-primary" href="<?php echo admin_url('edit.php'); ?>"><?php _e('Go back', 'textdomain'); ?></a>
    <button type="button" class="button dismiss-notification-dialog"><?php _e('View post', 'textdomain'); ?></button>
</p>
		<?php
	}
}
