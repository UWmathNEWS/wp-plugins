<?php
/**
 * Audit log page.
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

Utils::require_core('trait-mathnews-core-audit.php');

/**
 * Audit log page.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class AuditUI {
	use Core\Audit;

	public $slug = 'mn-audit-log';

	public const action_filters = [
		'cur_issue.update' => "Current Issue - Update",
		'post.approve' => "Article - Approve",
		'post.reject' => "Article - Reject",
		'post.delete' => "Article - Delete",
		'post.update' => "Article - Update",
		'plugin.create' => "Plugin - Activate",
		'plugin.delete' => "Plugin - Deactivate",
		'user.create' => "User - Add New",
		'user.delete' => "User - Delete",
		'user.update' => "User - Update",
	];

	public $messages;

	public function __construct() {
		$this->messages = [
			'cur_issue.update' => [
				'updated current issue settings',
				[
					'<li>Changed current issue from <code>{{log_message.old_tag}}</code> to <code>{{log_message.new_tag}}</code></li>',
					'<li v-if="log_message.num_posts">Moved {{log_message.num_posts}} article{{log_message.num_posts === 1 ? "" : "s"}} from <strong>pending</strong> to <strong>draft</strong></li>',
				]
			],
			'post.approve' => [
				'approved <a :href="`' . site_url() . '/?p=${log_target_id}&preview=true`">{{post_title}}</a>',
				[]
			],
			'post.reject' => [
				'rejected <a :href="`' . site_url() . '/?p=${log_target_id}&preview=true`">{{post_title}}</a>',
				[
					'<li>Gave rationale <code>{{log_message.rationale}}</code></li>',
					'<li v-if="log_message.notified">Notified user of rejection</li>',
					'<li v-if="log_message.returned">Moved from <strong>pending</strong> to <strong>draft</strong></li>',
				]
			],
			'post.delete' => [
				'deleted article <strong>{{log_message.post_title}}</strong>',
				[]
			],
			'post.update.status' => [
				'updated status of <a :href="`' . site_url() . '/?p=${log_target_id}&preview=true`">{{post_title}}</a>',
				[
					'<li>Moved from <strong>{{log_message.old_status}}</strong> to <strong>{{log_message.new_status}}</strong></li>',
				]
			],
			'plugin.create' => [
				'activated plugin <strong>{{log_message.plugin_location}}</strong>',
				[]
			],
			'plugin.delete' => [
				'deactivated plugin <strong>{{log_message.plugin_location}}</strong>',
				[]
			],
			'user.create' => [
				'added new user <strong>{{user_login}}</strong>',
				[
					'<li>Assigned role <strong>{{log_message.role}}</strong></li>',
				]
			],
			'user.delete' => [
				'deleted user <strong>{{user_login}}</strong>',
				[
					'<li v-if="log_message.reassigned_user">Reassigned articles to <strong>{{log_message.reassigned_user}}</strong>',
					'<li v-else>Deleted all articles</li>',
				]
			],
			'user.update.role' => [
				'updated roles for <strong>{{user_login}}</strong>',
				[
					'<li>Removed roles <code v-for="old_role in log_message.old_roles">{{old_role}}</code></li>',
					'<li>Added role <code>{{log_message.new_role}}</code></li>'
				]
			],
			'user.update.reset_password' => [
				'reset password for <strong>{{user_login}}</strong>',
				[]
			]
		];

		add_action('admin_menu', array($this, 'register_page'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

		add_action('wp_ajax_mn_audit_filter', array($this, 'handle_ajax'));
	}

	public function register_page() {
		add_management_page(__('Audit Log', 'textdomain'), __('Audit Log', 'textdomain'), 'manage_options',
			$this->slug, array($this, 'render_audit_ui_screen'));
	}

	public function enqueue_scripts() {
		if (get_current_screen()->base === "tools_page_{$this->slug}") {
			wp_enqueue_script('petite-vue', 'https://unpkg.com/petite-vue@0.4.1/dist/petite-vue.iife.js', [], null, true);
			wp_enqueue_script('mn-audit-ui', plugin_dir_url(__FILE__) . 'js/mathnews-core-audit-ui.js', ['petite-vue', 'mathnews-core'], Core\VERSION, true);
			wp_localize_script('mn-audit-ui', 'mn_audit_ui', [
				'nonce' => wp_create_nonce('mn-audit-ui'),
				'entries' => $this->get_log_entries($this->get_search_filters()),
				'filters' => self::action_filters,
				'users' => $this->get_actors(),
			]);
			wp_enqueue_style('mn-audit-ui', plugin_dir_url(__FILE__) . 'css/mathnews-core-audit-ui.css', [], Core\VERSION);
		}
	}

	public function handle_ajax() {
		check_ajax_referer('mn-audit-ui');

		if (!current_user_can('manage_options')) {
			wp_send_json_error();
		}

		if (isset($_GET)) {
			// TODO: write get handlers here, e.g. filter by actor or getting a specific log
			wp_send_json_success($this->get_log_entries($this->get_search_filters()));
		}

		wp_send_json_error();  // just in case
	}

	private function get_search_filters() {
		$filters = array_fill_keys(['log_actor_id', 'log_action'], 0);
		return array_filter(array_intersect_key($_GET, $filters));
	}

	private function get_log_entries(array $filters) {
		global $wpdb;

		$where_clauses = [];
		$where_args = [];
		foreach ($filters as $col => $val) {
			if ($col === 'log_actor_id') {
				$where_clauses[] = "$col = %d";
				$where_args[] = intval($val);
			} else if ($col === 'log_action') {
				$where_clauses[] = "$col LIKE %s";
				$where_args[] = $wpdb->esc_like($val) . '%';
			}
		}

		$where = '';

		if (count($where_clauses)) {
			$where = 'WHERE ' . implode(' AND ', $where_clauses);
			$where = $wpdb->prepare($where, $where_args);
		}

		$results = $wpdb->get_results(
			"SELECT
				l.*,
				{$wpdb->users}.user_login AS actor_login,
				{$wpdb->users}.display_name AS actor_name,
				p.post_title,
				u.user_login
			FROM $this->audit_table AS l
			LEFT JOIN $wpdb->users ON (log_actor_id = {$wpdb->users}.ID)
			LEFT JOIN $wpdb->posts AS p ON (log_action LIKE 'post.%' AND log_target_id = p.ID)
			LEFT JOIN $wpdb->users AS u ON (log_action LIKE 'user.%' AND log_target_id = u.ID)
			{$where}
			ORDER BY log_id DESC",
			ARRAY_A
		);

		foreach ($results as &$result) {
			$unit = strtok($result['log_action'], '.');
			$verb = strtok('.');
			$result['log_unit'] = $unit;
			$result['log_verb'] = $verb;
			$result['log_time'] = str_replace(' ', 'T', $result['log_time']);  // return in ISO 8601 format
			$result['log_time'] .= 'Z';  // specify this is a UTC timestamp
			$result['log_message'] = json_decode($result['log_message']);
			$result['actor_name'] = $result['actor_name'] ?? '[deleted user]';
			$result['post_title'] = $result['post_title'] ?? '[deleted article]';
			$result['post_title'] = htmlspecialchars_decode($result['post_title']);
			$result['user_login'] = $result['user_login'] ?? '[deleted user]';

			$result = array_filter($result);
		}
		unset($result);

		return $results;
	}

	private function get_actors() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT DISTINCT log_actor_id AS uid, user_login AS username
			FROM $this->audit_table
			LEFT JOIN $wpdb->users ON (log_actor_id = ID)
			ORDER BY display_name",
			ARRAY_A
		);
	}

	public function render_audit_ui_screen() {
		$log_entries = $this->get_log_entries([]);
		?>
<div class="wrap">
	<h1><?php echo __('Audit Log', 'textdomain'); ?></h1>

	<div id="mn-audit-log-mount" v-scope>
		<div class="mn-audit-log-filters">
			<h2>Filters</h2>

			<label for="mn-audit-log-actor">Filter user</label>
			<select id="mn-audit-log-actor" name="mn-audit-log-actor" @change="store.updateFilters('log_actor_id', $el.value)">
				<option value="" :selected="!store.filters.log_actor_id">All</option>
				<option v-for="actor in store.actors" :value="actor.uid" :selected="store.filters.log_actor_id === actor.uid">{{actor.username}}</option>
			</select>
			<label for="mn-audit-log-action">Filter action</label>
			<select id="mn-audit-log-action" name="mn-audit-log-action" @change="store.updateFilters('log_action', $el.value)">
				<option value="" :selected="!store.filters.log_action">All</option>
				<option v-for="(display, action) in store.filtersMap" :value="action" :selected="store.filters.log_action === action">{{display}}</option>
			</select>
			<button type="button" class="button" v-if="Object.keys(store.filters).length" @click="store.clearFilters()"> Clear filters</button>
		</div>

		<div class="mn-audit-log-list">
			<div
				v-for="entry in store.entries"
				v-scope="Entry({ entry })"
				:key="entry.log_id"
				class="mn-audit-log-entry"
				:data-action="entry.log_action.split('.').join(' ')"></div>
			<div v-if="store.entries.length === 0" class="mn-audit-log-entry mn-audit-log-empty">No results found.</div>
			<div v-show="store.loading" class="mn-audit-log-loading"><span class="spinner is-active"></span> Loading</div>
		</div>

		<template id="mn-audit-log-entry">
			<details>
				<summary class="log-entry-summary">
					<span class="log-entry-icon">
						<span :class="['unit', 'dashicons', `dashicons-${store.icons[log_unit]}`]"></span>
						<span :class="['verb', 'dashicons', `dashicons-${store.icons[log_verb]}`]"></span>
					</span>
					<div class="log-entry-heading">
						<p>
							<strong>{{actor_login}}</strong> ({{actor_name}})
							<?php
							foreach ($this->messages as $action => $template) {
								echo sprintf('<span v-if="log_action === \'%s\'">%s</span>', $action, $template[0]);
							}
							?>
						</p>
						<time :datetime="log_time" class="small">{{new Date(log_time).toLocaleString()}}</time>
					</div>
					<span class="log-entry-toggle dashicons dashicons-arrow-right"></span>
				</summary>
				<div class="log-entry-details">
					<?php
					foreach ($this->messages as $action => $template) {
						echo '<ol v-if="log_action === \'' . $action . '\'">';
						foreach ($template[1] as $item) {
							echo $item;
						}
						echo '</ol>';
					}
					?>
					<span class="small">Event #{{log_id}} &bull; {{log_action}}</span>
				</div>
			</details>
		</template>
	</div>
</div>
		<?php
	}
}
