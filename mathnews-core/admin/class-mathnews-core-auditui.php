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
		'page.create' => "Page - Create",
		'page.delete' => "Page - Delete",
		'page.update' => "Page - Update",
		'plugin.create' => "Plugin - Activate",
		'plugin.delete' => "Plugin - Deactivate",
		'settings.update' => "Settings - Update",
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
					'<li v-if="log_message.num_posts">Changed status of {{log_message.num_posts}} article{{log_message.num_posts === 1 ? "" : "s"}} from <strong>pending</strong> to <strong>draft</strong></li>',
				]
			],
			'post.approve' => [
				'approved <a :href="`' . site_url() . '/?p=${log_target_id}&preview=true`" target="_blank">{{post_title}}</a>',
				[
					'<li v-if="log_message.deltas?.tags">',
					'	<div v-if="log_message.deltas.tags.removed.length">Removed tags ',
					'		<span v-for="(tag, i) in log_message.deltas.tags.removed"><span v-if="i > 0">, </span><code>{{tag}}</code></span>',
					'	</div>',
					'	<div v-if="log_message.deltas.tags.added.length">Added tags ',
					'		<span v-for="(tag, i) in log_message.deltas.tags.added"><span v-if="i > 0">, </span><code>{{tag}}</code></span>',
					'	</div>',
					'</li>',
				]
			],
			'post.reject' => [
				'rejected <a :href="`' . site_url() . '/?p=${log_target_id}&preview=true`" target="_blank">{{post_title}}</a>',
				[
					'<li>Gave rationale <code>{{log_message.rationale}}</code></li>',
					'<li v-if="log_message.notified">Notified author of rejection</li>',
					'<li v-if="log_message.returned">Changed status from <strong>pending</strong> to <strong>draft</strong></li>',
					'<li v-if="log_message.deltas?.tags">',
					'	<div v-if="log_message.deltas.tags.removed.length">Removed tags ',
					'		<span v-for="(tag, i) in log_message.deltas.tags.removed"><span v-if="i > 0">, </span><code>{{tag}}</code></span>',
					'	</div>',
					'	<div v-if="log_message.deltas.tags.added.length">Added tags ',
					'		<span v-for="(tag, i) in log_message.deltas.tags.added"><span v-if="i > 0">, </span><code>{{tag}}</code></span>',
					'	</div>',
					'</li>',
				]
			],
			'post.delete' => [
				'deleted article <strong>{{log_message.post_title}}</strong>',
				[]
			],
			'post.update' => [
				'updated <a :href="`' . site_url() . '/?p=${log_target_id}&preview=true`" target="_blank">{{post_title}}</a>',
				[
					'<li v-for="(delta, field) in log_message.deltas">',
					'	<span v-if="delta.old && delta.new">Changed {{field}} from <strong>{{delta.old}}</strong> to <strong>{{delta.new}}</strong></span>',
					'	<div v-else-if="delta.removed?.length">Removed {{field}}',
					'		<span v-for="(thing, i) in delta.removed"><span v-if="i > 0">, </span><code>{{thing}}</code></span>',
					'	</div>',
					'	<div v-else-if="delta.added?.length">Added {{field}}',
					'		<span v-for="(thing, i) in delta.added"><span v-if="i > 0">, </span><code>{{thing}}</code></span>',
					'	</div>',
					'	<span v-else>Updated {{field}}</span>',
					'</li>',
				]
			],
			'page.create' => [
				'created page <strong>{{post_title}}</strong>',
				[
					'<li v-for="(delta, field) in log_message.deltas">',
					'	<span v-if="delta.new">Set {{field}} to <strong>{{delta.new}}</strong></span>',
					'	<div v-if="delta.added?.length">Added {{field}}',
					'		<span v-for="(thing, i) in delta.added"><span v-if="i > 0">, </span><code>{{thing}}</code></span>',
					'	</div>',
					'</li>',
				]
			],
			'page.delete' => [
				'deleted page <strong>{{log_message.post_title}}</strong>',
				[]
			],
			'page.update' => [
				'updated page <strong>{{post_title}}</strong>',
				[
					'<li v-for="(delta, field) in log_message.deltas">',
					'	<span v-if="delta.old && delta.new">Changed {{field}} from <strong>{{delta.old}}</strong> to <strong>{{delta.new}}</strong></span>',
					'	<div v-else-if="delta.removed?.length">Removed {{field}}',
					'		<span v-for="(thing, i) in delta.removed"><span v-if="i > 0">, </span><code>{{thing}}</code></span>',
					'	</div>',
					'	<div v-else-if="delta.added?.length">Added {{field}}',
					'		<span v-for="(thing, i) in delta.added"><span v-if="i > 0">, </span><code>{{thing}}</code></span>',
					'	</div>',
					'	<span v-else>Updated {{field}}</span>',
					'</li>',
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
			'settings.update' => [
				'updated setting <strong>{{log_message.option}}</strong>',
				[
					'<li v-if="log_message.old_value === null">Set value to <code>{{log_message.new_value}}</code></li>',
					'<li v-else>Changed value from <code>{{log_message.old_value}}</code> to <code>{{log_message.new_value}}</code></li>',
				]
			],
			'user.create' => [
				'added new user <strong>{{user_login}}</strong>',
				[
					'<li>Assigned role <strong>{{log_message.role}}</strong></li>',
				]
			],
			'user.delete' => [
				'deleted user <strong>{{log_message.user_login}}</strong>',
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
				'data' => $this->get_log_entries($this->get_search_filters()),
				'filters' => self::action_filters,
				'users' => $this->get_actors(),
			]);
			wp_enqueue_style('mn-audit-ui', plugin_dir_url(__FILE__) . 'css/mathnews-core-audit-ui.css', [], Core\VERSION);
		}
	}

	public function handle_ajax() {
		check_ajax_referer('mn-audit-ui');

		if (current_user_can('manage_options') && isset($_GET)) {
			wp_send_json_success($this->get_log_entries($this->get_search_filters()));
		}

		wp_send_json_error();  // just in case
	}

	private function get_search_filters() {
		$filters = array_fill_keys(['log_actor_id', 'log_action', 'log_id'], 0);
		return array_filter(array_intersect_key($_GET, $filters));
	}

	private function get_log_entries(array $filters) {
		global $wpdb;

		$where_clauses = [];
		$where_args = [];
		foreach ($filters as $col => $val) {
			if ($col === 'log_id') {
				$where_clauses[] = "$col < %d";
				$where_args[] = intval($val);
			} else if ($col === 'log_actor_id') {
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
			FROM {$this->audit_table()} AS l
			LEFT JOIN $wpdb->users ON (log_actor_id = {$wpdb->users}.ID)
			LEFT JOIN $wpdb->posts AS p ON ((log_action LIKE 'page.%' OR log_action LIKE 'post.%') AND log_target_id = p.ID)
			LEFT JOIN $wpdb->users AS u ON (log_action LIKE 'user.%' AND log_target_id = u.ID)
			{$where}
			ORDER BY log_id DESC
			LIMIT 20",
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

			if ($unit === 'post' || $unit === 'page') {
				$result['post_title'] = $result['post_title'] ?? "[deleted $unit]";
				$result['post_title'] = htmlspecialchars_decode($result['post_title']);
			}

			if ($unit === 'user') {
				$result['user_login'] = $result['user_login'] ?? '[deleted user]';
			}

			$result = array_filter($result);
		}
		unset($result);

		$count_remaining = $wpdb->get_var("SELECT COUNT(*) > 20 FROM {$this->audit_table()} {$where}");

		return [
			'entries' => $results,
			'more' => boolval($count_remaining),
		];
	}

	private function get_actors() {
		return get_users([
			'capability' => 'edit_others_posts',
			'fields' => ['ID', 'user_login', 'display_name'],
			'orderby' => 'user_login',
		]);
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
				<option v-for="actor in store.actors" :value="actor.ID" :selected="store.filters.log_actor_id === actor.ID">{{actor.user_login}} ({{actor.display_name}})</option>
			</select>
			<label for="mn-audit-log-action">Filter action</label>
			<select id="mn-audit-log-action" name="mn-audit-log-action" @change="store.updateFilters('log_action', $el.value)">
				<option value="" :selected="!store.filters.log_action">All</option>
				<option v-for="(display, action) in store.filtersMap" :value="action" :selected="store.filters.log_action === action">{{display}}</option>
			</select>
			<button type="button" class="button" v-if="Object.keys(store.filters).length" @click="store.clearFilters()"> Clear filters</button>
			<p class="small">
				Displaying actions from the past <?php echo get_option('mn_audit_persist_days', 90); ?> days.
				<a href="<?php echo add_query_arg('tab', 'advanced', menu_page_url(Consts\CORE_SETTINGS_SLUG, false)); ?>">Change</a>
			</p>
		</div>

		<div class="mn-audit-log-list">
			<noscript>JavaScript must be enabled to view the audit log.</noscript>
			<div
				v-for="entry in store.data.entries"
				v-scope="Entry({ entry })"
				:key="entry.log_id"
				class="mn-audit-log-entry"></div>
			<div v-if="store.data.entries.length === 0" class="mn-audit-log-entry mn-audit-log-empty hide-if-no-js">No results found.</div>
			<button v-if="store.data.more" @click="store.loadMore()" :disabled="store.loadingMore" class="button widefat hide-if-no-js">
				{{store.loadingMore ? 'Loading&hellip;' : 'Load More'}}
			</button>
			<div v-show="store.loading" class="mn-audit-log-loading hide-if-no-js"><span class="spinner is-active"></span> Loading</div>
		</div>

		<template id="mn-audit-log-entry">
			<details :data-filter="log_action_stem">
				<summary class="log-entry-summary">
					<span class="log-entry-icon" :title="store.filtersMap[log_action_stem]" aria-hidden="true">
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
					<span class="log-entry-toggle dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
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
					<div class="small">
						Event #{{log_id}} &bull;
						<a
							class="log-entry-filter"
							href=""
							:title="`Filter similar entries to ${store.filtersMap[log_action_stem]}`"
							:aria-label="`Filter similar entries to ${store.filtersMap[log_action_stem]}`"
							@click.prevent="store.updateFilters('log_action', log_action_stem)"
						>
							{{log_action}}
						</a>
					</div>
				</div>
			</details>
		</template>
	</div>
</div>
		<?php
	}
}
