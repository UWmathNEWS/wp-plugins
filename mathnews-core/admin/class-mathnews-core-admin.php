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

use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;
use Mathnews\WP\Core\Admin\Partials\Display;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Mathnews_Core_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * General settings class
	 * 
	 * @since    1.3.0
	 */
	private $settings;

	/**
	 * Current issue settings class
	 *
	 * @since 1.3.0
	 */
	private $current_issue_settings;

	/**
	 * Memoized data to be shared between methods
	 *
	 * @since 1.3.0
	 */
	private $memo;

	/**
	 * Enable or disable AB tests
	 *
	 * @since 1.0.0
	 * @access public
	 */
	const AB_TESTS_ENABLED = false;

	/**
	 * Determine if AB test is on for this user. Returns true if plugin should be enabled for user.
	 *
	 * @since 1.0.0
	 */
	static public function is_B_user($user_id) {
		return self::AB_TESTS_ENABLED && (user_can($user_id, 'edit_others_posts') || ($user_id % 2 === 0));
	}

	static public function is_B() {
		return self::is_B_user(get_current_user_id());
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		Utils::require(__FILE__, 'class-mathnews-core-settings.php');

		$this->settings = new Settings(Consts\CORE_SETTINGS_SLUG, __('mathNEWS Settings', 'textdomain'));
		$this->current_issue_settings = new Settings(Consts\CURRENT_ISSUE_SETTINGS_SLUG, __('Set Current Issue', 'textdomain'));

		$this->memo = [];

		Utils::require(__FILE__, 'partials/class-mathnews-core-admin-display.php');
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mathnews-core-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mathnews-core-admin.js',
			['jquery', 'wp-tinymce', 'media-upload', 'tags-box'], $this->version, true );
		wp_localize_script($this->plugin_name, 'mn_core', [
			'ajaxurl'       => admin_url('admin-ajax.php'),
			'currentIssue'  => esc_html('v' . implode('i', get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT))),
			'isCopyeditor'  => current_user_can('edit_others_posts'),
			'isAdmin'       => current_user_can('manage_options'),
			'currentScreen' => get_current_screen()->id,
			'categories'    => !current_user_can('manage_options') ? new \ArrayObject() : [
				'BACKISSUE_CAT_NAME' => get_cat_ID(Consts\BACKISSUE_CAT_NAME),
			],
		]);
	}

	/**
	 * Create custom category names
	 *
	 * @since 1.0.0
	 */
	public function create_categories() {
		if (!term_exists(Consts\APPROVED_CAT_NAME, 'category')) {
			wp_create_category(Consts\APPROVED_CAT_NAME);
		}
		if (!term_exists(Consts\REJECTED_CAT_NAME, 'category')) {
			wp_create_category(Consts\REJECTED_CAT_NAME);
		}
		if (!term_exists(Consts\BACKISSUE_CAT_NAME, 'category')) {
			wp_create_category(Consts\BACKISSUE_CAT_NAME);
		}
	}

	/**
	 * Register capabilities for administrators
	 *
	 * Unused for now.
	 *
	 * @since 1.0.0
	 */
	private function register_issue_caps() {
		$admin = get_role('administrator');  // only want to give admins permission to publish issues
		// capabilities from https://developer.wordpress.org/reference/functions/register_post_type/#capabilities
		$caps = [
			'edit_%s', 'read_%s', 'delete_%s',
			'edit_%ss', 'edit_others_%ss', 'publish_%ss', 'read_private_%ss',
			'read', 'delete_%ss', 'delete_private_%ss', 'delete_published_%ss', 'delete_others_%ss', 'edit_private_%ss', 'edit_published_%ss', 'edit_%ss'
		];
		foreach ($caps as $cap) {
			$admin->add_cap(sprintf($cap, Consts\ISSUE_TYPE));
		}
	}

	/**
	 * Register the article custom post type.
	 *
	 * Unused for now.
	 *
	 * @since 1.0.0
	 */
	static public function register_issue_cpt() {
		// It's static because we need to call this from the activator (see https://developer.wordpress.org/reference/functions/register_post_type/#flushing-rewrite-on-activation)
		$args_labels = [
			'name'                  => __('Issues', 'textdomain'),
			'singular_name'         => __('Issue', 'textdomain'),
			'menu_name'             => _x('Issues', 'Admin Menu text', 'textdomain'),
			'name_admin_bar'        => _x('Issue', 'Add New on Toolbar', 'textdomain'),
			'add_new'               => __('Add New', 'textdomain'),
			'add_new_item'          => __('Add New Issue', 'textdomain'),
			'new_item'              => __('New Issue', 'textdomain'),
			'edit_item'             => __('Edit Issue', 'textdomain'),
			'view_item'             => __('View Issue', 'textdomain'),
			'all_items'             => __('All Issues', 'textdomain'),
			'search_items'          => __('Search Issues', 'textdomain'),
			'parent_item_colon'     => __('Parent Issues:', 'textdomain'),
			'not_found'             => __('No issues found.', 'textdomain'),
			'not_found_in_trash'    => __('No issues found in Trash.', 'textdomain'),
			'archives'              => _x('Issue archives', 'archives', 'textdomain'),
			'insert_into_item'      => _x('Insert into issue', 'insert_into_item', 'textdomain'),
			'uploaded_to_this_item' => _x('Uploaded to this issue', 'uploaded_to_this_item', 'textdomain'),
			'filter_items_list'     => _x('Filter issues list', 'screen_reader', 'textdomain'),
			'items_list_navigation' => _x('Issues list navigation', 'screen_reader', 'textdomain'),
			'items_list'            => _x('Issues list', 'screen_reader', 'textdomain'),
		];
		$args_supports = [
			'title',
			'editor',
			'author',
			'thumbnail'
		];
		$args_rewrite = [
			'slug' => 'issue',
			'with_front' => false
		];
		$args = [
			'labels'            => $args_labels,
			'public'            => true,
			'menu_position'     => 5,
			'taxonomies'        => ['post_tag'],
			'capability_type'   => [Consts\ISSUE_TYPE, Consts\ISSUE_TYPE . 's'],
			'supports'          => $args_supports,
			'rewrite'           => $args_rewrite,
			'has_archive'       => 'issue'
		];

		register_post_type(Consts\ISSUE_TYPE, $args);
	}

	/**
	 * Set PHPMailer config
	 *
	 * @since 1.3.0
	 * @uses phpmailer_init
	 */
	public function phpmailer_init($mailer)	{
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
	public function phpmailer_error_handler($error)	{
		global $phpmailer;
		wp_die(var_dump(get_option('mn_email_smtp_use')));
	}

	/**
	 * Register settings options
	 *
	 * @since 1.2.0
	 */
	public function register_settings() {
		$this->settings->add_section('admin-notice', 'Admin Notice')
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

		$this->settings->add_section('writing', 'Writing')
			->register(
				Consts\HELPFUL_LINKS_OPTION_NAME,
				__('Helpful links', 'textdomain'),
				'textarea',
				'',
				[
					'description' => 'Shown in the post writing screen. One link per line, in the format <code>URL link_title</code>.',
					'attrs' => ['rows' => 5],
				]
			);

		$this->settings->add_section('email', 'Email')
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

		/**
		 * Allow for registration of additional settings to the mathNEWS settings screen
		 * 
		 * @since 1.3.0
		 */
		do_action('mathnews-core:add_settings', $this->settings);

		$this->settings->run();
	}

	/**
	 * Add settings screen
	 *
	 * @since 1.2.0
	 */
	public function add_settings_screen() {
		$this->settings->render_screen();
	}

	/**
	 * Enqueue scripts for the settings screen
	 *
	 * @since 1.3.0
	 */
	public function enqueue_settings_scripts() {
		if (get_current_screen()->base === 'settings_page_' . Consts\CORE_SETTINGS_SLUG) {
			wp_enqueue_script( $this->plugin_name . Consts\CORE_SETTINGS_SLUG,
				plugin_dir_url( __FILE__ ) . 'js/mathnews-core-settings.js', ['jquery'], $this->version, true );
		}
	}

	/**
	 * Register current issue option
	 *
	 * @since 1.0.0
	 */
	public function register_current_issue_settings() {
		$s = $this->current_issue_settings->add_section('settings', '', array(Display::class, 'render_current_issue_settings_description'));
		$s->register(
			Consts\CURRENT_ISSUE_OPTION_NAME,
			__('Current volume and issue', 'textdomain'),
			array(Display::class, 'render_current_issue_settings_fields'),
			Consts\CURRENT_ISSUE_OPTION_DEFAULT
		);

		$cur_issue = get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT);
		$cur_tag = "v${cur_issue[0]}i${cur_issue[1]}";

		$s->register(
			Consts\CURRENT_ISSUE_OPTION_NAME . '_change',
			__(''),
			'checkbox',
			['off'],
			[
				'labels' => [sprintf(__('Move all posts tagged as %s to draft status'), "<code>$cur_tag</code>")],
				'dummy' => true,
				'attrs' => [
					'id'       => 'current-issue-tag-return',
					'disabled' => true,
				],
			]
		);

		$this->current_issue_settings->run();
	}

	/**
	 * Add current issue settings screen
	 *
	 * @since 1.0.0
	 */
	public function add_current_issue_settings_screen() {
		add_posts_page(__('Set Current Issue', 'textdomain'), __('Set Current Issue', 'textdomain'), 'manage_options',
			Consts\CURRENT_ISSUE_SETTINGS_SLUG, array(Display::class, 'render_current_issue_settings_screen'));
	}

	/**
	 * Enqueue scripts for the current issue settings screen
	 *
	 * @since 1.0.0
	 */
	public function enqueue_current_issue_settings_scripts() {
		if (get_current_screen()->base === 'posts_page_' . Consts\CURRENT_ISSUE_SETTINGS_SLUG) {
			wp_enqueue_script( $this->plugin_name . Consts\CURRENT_ISSUE_SETTINGS_SLUG,
				plugin_dir_url( __FILE__ ) . 'js/mathnews-core-set-current-issue.js', ['jquery', 'wp-tinymce'], $this->version, true );
		}
	}

	/**
	 * Change status of pending posts for the given tag to draft
	 *
	 * @since 1.3.0
	 * @uses update_option_${Consts\CURRENT_ISSUE_OPTION_NAME}
	 */
	public function move_current_issue_pending_to_draft($cur_issue) {
		global $wpdb;

		if ($_POST[Consts\CURRENT_ISSUE_OPTION_NAME . '_change']) {
			$cur_tag = "v{$cur_issue[0]}i{$cur_issue[1]}";

			// Unfortunately, this fires before the Wordpress posts query is initialized, so we cannot use get_posts, nor can
			// we use WP_Query. Hence, we are forced to make a direct DB query to update the post status.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} AS p
						INNER JOIN {$wpdb->term_relationships} AS r ON (r.object_id = p.ID)
						INNER JOIN {$wpdb->term_taxonomy} AS tx ON (tx.term_taxonomy_id = r.term_taxonomy_id)
						INNER JOIN {$wpdb->terms} AS t ON (t.term_id = tx.term_id)
					SET p.post_status = %s
						WHERE p.post_status = %s AND p.post_type = %s AND t.name = %s",
					['draft', 'pending', 'post', $cur_tag]
				)
			);
		}
	}

	/**
	 * Remove quick draft widget from dashboard.
	 *
	 * This is because WordPress will format posts created using this widget as if they were composed in the
	 * block editor. This introduces extra formatting that breaks our import scripts and also, at times, the
	 * Classic Editor.
	 *
	 * @since 1.2.0
	 */
	public function remove_quick_draft_widget()	{
		remove_meta_box('dashboard_quick_press', 'dashboard', 'normal');
		if (current_user_can('manage_options')) {
			$cur_issue = get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT);
			$cur_tag = "v${cur_issue[0]}i${cur_issue[1]}";
			$title = sprintf(__('Authors for %s', 'textdomain'), $cur_tag);
			add_meta_box('mn-authors', $title, array(Display::class, 'render_current_issue_authors_meta_box'), 'dashboard', 'normal');
		}
	}

	/**
	 * Lock post if status is pending
	 *
	 * @since 1.0.0
	 */
	public function show_editor_lock_warning() {
		global $post;

		if (Utils::can_edit($post)) {
			return;
		}

		Display::notification_dialog('mn-editor-lock-warning', __('This post is locked for publishing', 'textdomain'),
			array(Display::class, 'render_editor_lock_dialog'), false);
	}

	/**
	 * Lock TinyMCE
	 *
	 * @since 1.0.0
	 */
	public function lock_tinymce($mceInit) {
		global $post;

		if (!Utils::can_edit($post)) {
			$mceInit['readonly'] = true;
		}

		return $mceInit;
	}

	/**
	 * Prevent categories from showing in quick edit for non-editors
	 *
	 * @since 1.0.0
	 */
	public function remove_categories_from_quickedit($show, $taxonomy_name, $post_type) {
		if (!current_user_can('edit_others_posts') && in_array($taxonomy_name, ['category', '_status'])) {
			return false;
		}

		return $show;
	}

	/**
	 * Prevent users from trashing articles they've submitted
	 *
	 * @since 1.0.0
	 */
	public function modify_post_row_actions($actions, $post) {
		if (!Utils::can_edit($post)) {
			unset($actions['inline hide-if-no-js']);
			unset($actions['trash']);
		}

		return $actions;
	}

	/**
	 * Add current issue tag to articles that were submitted without a tag
	 *
	 * @since 1.0.0
	 */
	public function add_current_issue_tag($post_id, $post) {
		$current_tags = get_the_tags($post_id);
		if ($current_tags === false) {
			$cur_issue = get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT);  // [volume_num, issue_num]
			$default_tag = "v{$cur_issue[0]}i{$cur_issue[1]}";
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
		wp_set_post_categories($post_id, []);
	}

	/**
	 * Add a link to the list of pending posts
	 *
	 * @since 1.1.1
	 */
	public function link_to_pending() {
		add_submenu_page('edit.php', __('Pending') . ' ' . __('Posts'), __('Pending') . ' ' . __('Posts'),
			'edit_others_posts',
			add_query_arg([
				'post_status' => 'pending',
				'post_type' => 'post',
				'orderby' => 'date',
				'order' => 'desc',
			], admin_url('edit.php')),
			'', 1);
	}

	/**
	 * Colourize categories and select tags so they're more visible
	 *
	 * @since 1.1.1
	 */
	public function colourize_categories($term_links, $taxonomy, $terms) {
		if (!current_user_can('edit_others_posts')) {
			return $term_links;
		}

		$n = count($terms);

		if ($taxonomy === 'category') {
			for ($i = 0; $i < $n; $i++) {
				if (in_array($terms[$i]->name, [Consts\APPROVED_CAT_NAME, 'Uncategorized'], true)) {
					$term_links[$i] = sprintf('<span class="taxonomy-pill category--%s">%s</span>', $terms[$i]->slug, $term_links[$i]);
					break;
				}
			}
		} elseif ($taxonomy === 'post_tag') {
			$cur_issue = get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT);
			$default_tag = "v{$cur_issue[0]}i{$cur_issue[1]}";
			for ($i = 0; $i < $n; $i++) {
				if ($terms[$i]->name === $default_tag) {
					$term_links[$i] = sprintf('<span class="taxonomy-pill tag--current-issue">%s</span>', $term_links[$i]);
				}
			}
		}

		return $term_links;
	}

	/**
	 * Show the pseudonym used on an article in the author column of the posts table
	 * 
	 * @since 1.3.0
	 * @uses the_author
	 */
	public function show_pseudonym_as_author($display_name) {
		global $post;
		$nickname = get_post_meta($post->ID, Consts\AUTHOR_META_KEY_NAME, true) ?: $display_name;

		if (current_user_can('manage_options')) {
			$cur_issue = get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT);
			$cur_tag = "v${cur_issue[0]}i${cur_issue[1]}";
			$count = count(get_posts([
				'numberposts' => -1,
				'post_status' => 'any',
				'author' => $post->post_author,
				'tag' => $cur_tag,
			]));

			$html = esc_html($nickname);
			$html .= '<details>';
			// <a> here ensures that the <summary> is clickable
			$html .= '<summary><a><em>' . $display_name . ' (' . $count . ')</em></a></summary>'; 
			$html .= '<a href="' . get_edit_user_link($post->post_author) . '">Edit user profile</a>';
			$html .= '</details>';
			return $html;
		} elseif (current_user_can('edit_others_posts') || $post->post_author == get_current_user_id()) {
			return esc_html($nickname) . ' <em>(' . $display_name . ')</em>';
		}

		// user doesn't have editing privileges, so we only show the pseudonym.
		// echo here will output text without wrapping it in a link to all of the author's posts, to preserve privacy
		echo esc_html($nickname);
		return null;
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

	/**
	 * Handle post approvals and rejections
	 *
	 * @since 1.0.0
	 */
	public function handle_post_approval($post_id, $post) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], basename(__FILE__))) {
			return $post_id;
		}

		$post_type        = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		$can_approve      = current_user_can('edit_others_posts');
		$is_approved      = isset($_POST['mn-approve']);
		$is_rejected      = isset($_POST['mn-reject']);

		if ($post_type !== Consts\POST_TYPE || !$can_approve || !($is_approved || $is_rejected)) {
			return $post_id;
		}

		// let rejection take precedence over approval
		if ($is_rejected) {
			$rejected_cat = get_cat_ID(Consts\REJECTED_CAT_NAME);

			wp_set_post_categories($post_id, $rejected_cat);

			if (isset($_POST['mn-reject-draft'])) {
				wp_set_post_tags($post_id, []);
			}

			if (isset($_POST['mn-reject-email'])) {
				$reject_rationale = isset($_POST['mn-reject-rationale']) ? $_POST['mn-reject-rationale'] : '';
				
				// TODO: add error interface
				$this->notify_author_on_reject($post, wp_strip_all_tags($reject_rationale), $_POST['mn-reject-draft'] ?? 0);
			}
		} elseif ($is_approved) {
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
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], basename(__FILE__))) {
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
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], basename(__FILE__))) {
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
	 * Handle saving subtitle
	 *
	 * @since 1.0.0
	 */
	public function save_subtitle_post_meta($post_id, $post) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], basename(__FILE__))) {
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
	 */
	public function save_author_post_meta($post_id, $post) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], basename(__FILE__))) {
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
	 */
	public function save_postscript_post_meta($post_id, $post) {
		if (empty($_POST) || !isset($_POST['mn-submit-nonce']) || !wp_verify_nonce($_POST['mn-submit-nonce'], basename(__FILE__))) {
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
	 * Setup meta box. Called by do_action('load-page.php')
	 *
	 * @since 1.0.0
	 */
	public function meta_box_setup() {
		add_action('add_meta_boxes', array($this, 'create_publish_meta_boxes'));
		add_action('add_meta_boxes', array($this, 'remove_extraneous_meta_boxes'));
		add_action('add_meta_boxes', array($this, 'remove_categories_meta_box'));
	}

	/**
	 * Remove format, excerpt, trackbacks, discussion, slug, and custom fields meta boxes
	 *
	 * @since 1.0.0
	 */
	public function remove_extraneous_meta_boxes() {
		remove_meta_box('formatdiv', 'post', 'side');
		remove_meta_box('postimagediv', 'post', 'side');

		remove_meta_box('postexcerpt', 'post', 'normal');
		remove_meta_box('trackbacksdiv', 'post', 'normal');
		remove_meta_box('commentstatusdiv', 'post', 'normal');
		remove_meta_box('slugdiv', 'post', 'normal');
		remove_meta_box('postcustom', 'post', 'normal');
		remove_meta_box('authordiv', 'post', 'normal');
	}

	/**
	 * Remove categories meta box for non-editors
	 *
	 * @since 1.0.0
	 */
	public function remove_categories_meta_box() {
		if (!current_user_can('edit_others_posts')) {
			remove_meta_box('categorydiv', 'post', 'side');
		}
	}

	/**
	 * Create meta boxes related to mathNEWS custom submission flow
	 *
	 * @since 1.0.0
	 */
	public function create_publish_meta_boxes() {
		global $post;  // i hate this but it's the easiest way to get the current post

		$post_id = $post->ID;
		$existing_cats = get_the_category($post_id);
		$post_to_be_published = 0 < count(array_filter($existing_cats, function($term) {
			return $term->name === Consts\BACKISSUE_CAT_NAME;
		}));
		$screen_is_publish = current_user_can('manage_options') && isset($_GET) && isset($_GET['mn-publish']);

		// only apply to posts that aren't backissue posts
		if (get_post_type() === Consts\POST_TYPE && !$post_to_be_published && !$screen_is_publish) {
			// replace publish meta box
			remove_meta_box('submitdiv', 'post', 'side');
			add_meta_box('mn-submitdiv', __('Submit', 'textdomain'), array($this, 'render_publish_meta_box'), 'post', 'side', 'high');

			// add subtitle input
			add_action('edit_form_before_permalink', array($this, 'add_subtitle_input'));

			// add writer's guide notice box
			add_meta_box('mn-helpful-links', __('Helpful Links', 'textdomain'), array($this, 'render_helpful_links_meta_box'), 'post', 'side', 'high');

			// add postscript meta box
			$postscript_meta_box_id = 'mn-postscriptdiv';
			add_meta_box($postscript_meta_box_id, __('Postscript', 'textdomain'), array($this, 'render_postscript_meta_box'), 'post', 'normal', 'high');
			add_filter("postbox_classes_post_${postscript_meta_box_id}", array($this, 'close_postscript_meta_box_by_default'));
		}
	}

	/**
	 * Wrapper for rendering the publish meta box
	 *
	 * @since 1.0.0
	 */
	public function render_publish_meta_box($post) {
		$nonce_field = wp_nonce_field(basename(__FILE__), 'mn-submit-nonce', true, false);
		Display::render_publish_meta_box($post, $nonce_field, Utils::can_edit($post));
	}

	/**
	 * Add subtitle text input
	 *
	 * @since 1.0.0
	 */
	public function add_subtitle_input($post) {
		$subtitle = get_post_meta($post->ID, Consts\SUBTITLE_META_KEY_NAME, true);

		Display::subtitle_input($subtitle, Utils::can_edit($post));
	}

	/**
	 * Wrapper for rendering the helpful links meta box
	 *
	 * @since 1.2.0
	 */
	public function render_helpful_links_meta_box($post) {
		Display::render_helpful_links_meta_box();
	}

	/**
	 * Render the postscript meta box
	 *
	 * @since 1.0.0
	 */
	public function render_postscript_meta_box($post) {
		$settings = [
			'media_buttons' => false,
			'textarea_rows' => 5,
			'teeny' => true,
		];
		wp_editor(get_post_meta($post->ID, Consts\POSTSCRIPT_META_KEY_NAME, true), 'mn-postscript', $settings);
	}

	/**
	 * Hide postscript meta box if not an editor and there's no postscript
	 *
	 * @since 1.0.0
	 */
	public function close_postscript_meta_box_by_default($classes) {
		global $post;

		if (!current_user_can('edit_others_posts') && get_post_meta($post->ID, Consts\POSTSCRIPT_META_KEY_NAME, true) === '') {
			$classes[] = 'closed';
		}

		return $classes;
	}

	/**
	 * Display a marker beside the names of authors who are enroled in the AB test
	 *
	 * @since 1.0.0
	 */
	public function filter_author_AB($display_name) {
		global $authordata;
		if (self::AB_TESTS_ENABLED && current_user_can('manage_options') && !is_null($display_name) && self::is_B_user($authordata->ID)) {
			return $display_name . ' <em>(*)</em>';
		}

		return $display_name;
	}

	/**
	 * Display a feedback notice for AB users
	 *
	 * @since 1.0.0
	 */
	public function feedback_notice() {
		if (!self::is_B()) { return; }

		Display::feedback_notice();
	}

	/**
	 * Display an admin notice for users
	 *
	 * @since 1.3.0
	 */
	public function admin_notice() {
		$notice_type = get_option('mn_admin_notice_type', 'none');

		if ($notice_type === 'none') { return; }

		Display::admin_notice();
	}
}
