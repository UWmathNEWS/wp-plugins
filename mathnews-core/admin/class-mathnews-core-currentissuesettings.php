<?php
/**
 * Functionality for the current issue settings page.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.4.0
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 */

namespace Mathnews\WP\Core\Admin;

use Mathnews\WP\Core;
use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;

Utils::require_core('class-mathnews-core-settings.php');

/**
 * Functionality for the current issue settings page.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class CurrentIssueSettings {
	/**
	 * General settings class
	 *
	 * @since    1.3.0
	 */
	private $settings;

	public function __construct() {
		$this->settings = new Core\Settings(Consts\CURRENT_ISSUE_SETTINGS_SLUG, __('Set Current Issue', 'textdomain'));

		add_action('admin_menu', array($this, 'add_current_issue_settings_screen'));
		add_action('admin_init', array($this, 'register_current_issue_settings'));
		add_action('mn_settings_enqueue_scripts_' . Consts\CURRENT_ISSUE_SETTINGS_SLUG, array($this, 'enqueue_current_issue_settings_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('update_option_' . Consts\CURRENT_ISSUE_OPTION_NAME, $plugin_admin, 'move_current_issue_pending_to_draft');
	}

	/**
	 * Register current issue option
	 *
	 * @since 1.0.0
	 * @uses admin_init
	 */
	public function register_current_issue_settings() {
		$cur_tag = Utils::get_current_tag();

		$this->settings->add_section('settings', '', array(self::class, 'render_current_issue_settings_description'))
			->register(
				Consts\CURRENT_ISSUE_OPTION_NAME,
				__('Current volume and issue', 'textdomain'),
				array(self::class, 'render_current_issue_settings_fields'),
				Consts\CURRENT_ISSUE_OPTION_DEFAULT
			)
			->register(
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

		$this->settings->run();
	}

	/**
	 * Add current issue settings screen
	 *
	 * @since 1.0.0
	 * @uses admin_menu
	 */
	public function add_current_issue_settings_screen() {
		add_posts_page(__('Set Current Issue', 'textdomain'), __('Set Current Issue', 'textdomain'), 'manage_options',
			Consts\CURRENT_ISSUE_SETTINGS_SLUG, array(self::class, 'render_current_issue_settings_screen'));
	}

	/**
	 * Enqueue scripts for the current issue settings screen
	 *
	 * @since 1.0.0
	 * @uses mn_settings_enqueue_scripts_{Consts\CURRENT_ISSUE_SETTINGS_SLUG}
	 */
	public function enqueue_current_issue_settings_scripts() {
		wp_enqueue_script(Consts\CURRENT_ISSUE_SETTINGS_SLUG,
			plugin_dir_url(__FILE__) . 'js/mathnews-core-set-current-issue.js', ['jquery'], Core\VERSION, true);
	}

	/**
	 * Enqueue settings scripts
	 *
	 * @since 1.4.0
	 * @uses admin_enqueue_scripts
	 */
	public function enqueue_scripts() {
		$this->settings->enqueue_scripts('posts_page_');
	}

	/**
	 * Change status of pending posts for the given tag to draft
	 *
	 * @since 1.3.0
	 * @uses update_option_${Consts\CURRENT_ISSUE_OPTION_NAME}
	 */
	public function move_current_issue_pending_to_draft(array $cur_issue) {
		global $wpdb;

		if ($_POST[Consts\CURRENT_ISSUE_OPTION_NAME . '_change']) {
			$cur_tag = Utils::get_current_tag($cur_issue);

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
	 * Renders a screen to set the current issue
	 *
	 * @since 1.0.0
	 */
	static public function render_current_issue_settings_screen() {
		?>
<div class="wrap">
    <h1><?php _e('Set Current Issue', 'textdomain'); ?></h1>
    <?php settings_errors(); ?>
    <form action="options.php" method="post">
        <?php
        settings_fields(Consts\CURRENT_ISSUE_SETTINGS_SLUG);
        do_settings_sections(Consts\CURRENT_ISSUE_SETTINGS_SLUG);
        ?>
        <button type="submit" name="submit" id="submit" class="button button-primary button-large">
            Set current issue tag to
            <code><span id="current-issue-tag"><?php echo esc_html(Utils::get_current_tag()); ?></span></code>
        </button>
    </form>
</div>
		<?php
	}

	/**
	 * Renders description for setting the current issue
	 *
	 * @since 1.0.0
	 */
	static public function render_current_issue_settings_description() {
		?>
<p>
    Enter the volume and issue number for the upcoming issue.
    This will set the default tag to be applied when a writer submits an article.
</p>
		<?php
	}

	/**
	 * Renders fields to set the current issue
	 *
	 * @since 1.0.0
	 */
	static public function render_current_issue_settings_fields(string $option_name, array $default, array $args) {
		$cur_issue = get_option($option_name, $default);  // [volume_num, issue_num]
		?>
<label for="current-issue-tag-volume">Volume</label>
<input type="text" id="current-issue-tag-volume" name="<?php echo esc_attr($option_name); ?>[0]" value="<?php echo esc_attr($cur_issue[0]); ?>" size="3" />
<label for="current-issue-tag-issue">Issue</label>
<input type="text" id="current-issue-tag-issue" name="<?php echo esc_attr($option_name); ?>[1]" value="<?php echo esc_attr($cur_issue[1]); ?>" size="1" />
		<?php
	}
}
