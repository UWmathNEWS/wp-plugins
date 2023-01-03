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

Utils::require_core('trait-mathnews-core-audit.php');

/**
 * Article submission flow.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class ArticleApproval {
	use Core\Audit;

	public function __construct() {
		add_action('save_post_post', array($this, 'handle_post_approval'), 10, 2);
		add_filter('wp_insert_post_data', array($this, 'prepend_rejection_rationale'));
		add_filter('wp_insert_post_data', array($this, 'normalize_post_status_on_approval'));
	}

	/**
	 * Handle post approvals and rejections
	 *
	 * @since 1.0.0
	 * @uses save_post_post
	 */
	public function handle_post_approval($post_id, $post) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], dirname(__FILE__))) {
			return $post_id;
		}

		$post_type        = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		$can_approve      = current_user_can('edit_others_posts');
		$is_approved      = isset($_POST['mn-approve']);
		$is_rejected      = isset($_POST['mn-reject']);
		$reject_rationale = isset($_POST['mn-reject-rationale']) ? $_POST['mn-reject-rationale'] : '';

		if ($post_type !== Consts\POST_TYPE || !$can_approve || !($is_approved || $is_rejected)) {
			return $post_id;
		}

		// let rejection take precedence over approval
		if ($is_rejected) {
			$this->audit('post.reject', get_current_user_id(), $post_id, [
				'returned' => isset($_POST['mn-reject-draft']),
				'notified' => isset($_POST['mn-reject-email']),
				'rationale' => wp_strip_all_tags(strtok($reject_rationale, ".\r\n")),
			]);

			$rejected_cat = get_cat_ID(Consts\REJECTED_CAT_NAME);

			wp_set_post_categories($post_id, $rejected_cat);

			if (isset($_POST['mn-reject-draft'])) {
				wp_set_post_tags($post_id, []);
			}

			if (isset($_POST['mn-reject-email'])) {
				// TODO: add error interface
				$this->notify_author_on_reject($post, wp_strip_all_tags($reject_rationale), $_POST['mn-reject-draft'] ?? 0);
			}
		} elseif ($is_approved) {
			$this->audit('post.approve', get_current_user_id(), $post_id, []);

			$approved_cat = get_cat_ID(Consts\APPROVED_CAT_NAME);

			wp_set_post_categories($post_id, $approved_cat);
		}
	}

	/**
	 * Prepend rejection rationale to content
	 *
	 * @since 1.0.0
	 * @uses wp_insert_post_data
	 */
	public function prepend_rejection_rationale($data) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], dirname(__FILE__))) {
			return $data;
		}

		$post_type        = $data['post_type'];
		$post_type_object = get_post_type_object( $post_type );
		$can_approve      = current_user_can('edit_others_posts');
		$is_rejected      = isset($_POST['mn-reject']);
		$reject_rationale = isset($_POST['mn-reject-rationale']) ? $_POST['mn-reject-rationale'] : '';

		if ($post_type !== Consts\POST_TYPE || !$can_approve || !$is_rejected || $reject_rationale === '') {
			return $data;
		}

		$original_content = preg_replace('/^REASON FOR REJECTION:[\s\S]*?---(\r?\n\r?\n)?/', '', $data['post_content']);

		$data['post_content'] = "REASON FOR REJECTION:\n" . wp_strip_all_tags($reject_rationale) . "\n---\n\n" . $original_content;

		return $data;
	}

	/**
	 * Automatically set post status as pending if approved, and draft as rejected (to give the writer a chance to workshop it)
	 *
	 * @since 1.0.0
	 * @uses wp_insert_post_data
	 */
	public function normalize_post_status_on_approval($data) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], dirname(__FILE__))) {
			return $data;
		}

		$post_type        = $data['post_type'];
		$post_type_object = get_post_type_object( $post_type );
		$can_approve      = current_user_can('edit_others_posts');
		$is_approved      = isset($_POST['mn-approve']);
		$is_rejected      = isset($_POST['mn-reject']);
		$return_to_author = $_POST['mn-reject-draft'] ?? 0;

		if ($post_type !== Consts\POST_TYPE || !$can_approve || !($is_approved || $is_rejected)) {
			return $data;
		}

		// let rejection take precedence over approval
		if ($is_rejected && $return_to_author) {
			$data['post_status'] = 'draft';
		} elseif ($is_approved) {
			$data['post_status'] = 'pending';
		}

		return $data;
	}

	/**
	 * Email an author if their post was rejected
	 *
	 * @since 1.3.0
	 */
	private function notify_author_on_reject($post, $reject_rationale, $show_edit_link = false) {
		$authordata = get_userdata($post->post_author);
		if (!$authordata) return false;

		$domain = substr(site_url('', 'http'), 7);
		$to = $authordata->user_email;
		$subject = '[' . get_bloginfo('name') . '] Rejection Notice';
		$headers = [
			'From: ' . sprintf('%s <noreply@%s>', get_bloginfo('name'), $domain),
			'Reply-To: ' . get_bloginfo('admin_email'),
		];

		$title = $post->post_title;
		$edit_message = '';
		$reject_rationale = wordwrap(stripslashes($reject_rationale), 72, "\n\t");
		$author_name = $authordata->first_name;

		if ($show_edit_link) {
			$edit_link = get_edit_post_link($post, '&');
			$edit_message = <<<MSG

You may edit and resubmit your article by visiting the following link:
$edit_link

MSG;
		}

		$message = <<<MSG
Hello $author_name,

Your article "$title" was rejected for the following reason:

	$reject_rationale
$edit_message
Regards,
The mathNEWS Editors
MSG;

		return wp_mail($to, $subject, $message, $headers);
	}
}
