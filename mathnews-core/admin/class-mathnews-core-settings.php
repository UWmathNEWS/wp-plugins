<?php
/**
 * Settings helper for the plugin and its dependents.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.3.0
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 */

namespace Mathnews\WP\Core\Admin;

use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;

class SettingsField {
	public $id;
	public $label;

	private $callback;
	private $default;
	private $args;

	public function __construct(string $id, string $label, $callback, $default, array $args) {
		$this->id = $id;
		$this->label = $label;
		$this->callback = $callback;
		$this->default = $default;
		$this->args = $args;
	}

	public function is_dummy(): bool {
		return isset($this->args['dummy']) ? true : false;
	}

	public function _do_dummy() {
		if (is_callable($this->args['dummy'])) {
			call_user_func($this->args['dummy']);
		}
	}

	public function label_for(): string {
		return in_array($this->callback, ['radio', 'checkbox']) || $this->args['no_label'] ? '' : ($this->id . '-input');
	}

	public function render() {
		if ($this->callback === 'text') {
			$this->render_text_field();
		} elseif ($this->callback === 'textarea') {
			$this->render_textarea_field();
		} elseif ($this->callback === 'editor') {
			$this->render_editor_field();
		} elseif ($this->callback === 'checkbox') {
			$this->render_checkbox_field();
		} elseif ($this->callback === 'radio') {
			$this->render_radio_field();
		} elseif ($this->callback === 'select') {
			$this->render_select_field();
		} elseif (is_callable($this->callback)) {
			call_user_func($this->callback, $this->id, get_option($this->id, $this->default), $this->args);
		} else {
			wp_die('Non-recognized callback provided');
		}
	}

	private function render_text_field() {
		$mandatory_attrs = [
			'type'  => 'text',
			'name'  => esc_attr($this->id),
			'value' => esc_attr(get_option($this->id, $this->default)),
		];
		$default_attrs = [
			'id'   => esc_attr($this->id) . '-input',
			'size' => 30,
		];
		$attrs = array_merge($default_attrs, $this->args['attrs'] ?? [], $mandatory_attrs);

		echo sprintf('<input %s />', Utils::build_attrs_from_array($attrs));
		if (!empty($this->args['description'])) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}
	}

	private function render_textarea_field() {
		$mandatory_attrs = [
			'name' => esc_attr($this->id),
		];
		$default_attrs = [
			'id'    => esc_attr($this->id) . '-input',
			'rows'  => 10,
			'class' => 'large-text',
		];
		$attrs = array_merge($default_attrs, $this->args['attrs'] ?? [], $mandatory_attrs);

		echo sprintf('<textarea %2$s>%1$s</textarea>',
			esc_attr(get_option($this->id, $this->default)),
			Utils::build_attrs_from_array($attrs));
		if (!empty($this->args['description'])) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}
	}

	private function render_editor_field() {
		$mandatory_attrs = [
			'data-editor-id' => esc_attr($this->id) . '-input',
		];
		$default_attrs = [
			'id' => esc_attr($this->id) . 'wrapper',
		];
		$attrs = array_merge($default_attrs, $this->args['attrs'] ?? [], $mandatory_attrs);

		$args = array_merge([
			'media_buttons' => false,
			'teeny'         => true,
		], $this->args['editor'] ?? [], ['textarea_name' => $this->id]);

		echo sprintf('<div %s>', Utils::build_attrs_from_array($attrs));
		wp_editor(get_option($this->id, $this->default), $this->id . '-input', $args);
		echo '</div>';
		if (!empty($this->args['description'])) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}
	}

	private function render_checkbox_field() {
		echo '<fieldset>';
		foreach ($this->args['labels'] as $ind => $label) {
			$mandatory_attrs = [
				'type'    => 'checkbox',
				'name'    => sprintf('%s[%s]', esc_attr($this->id), esc_attr($ind)),
				'checked' => get_option($this->id, $this->default)[$ind] === 'on',
			];
			$default_attrs = [
				'id' => sprintf('%s[%s]-input', esc_attr($this->id), esc_attr($ind)),
			];
			$attrs = array_merge($default_attrs,
				is_array($this->args['attrs'][$ind]) ? $this->args['attrs'][$ind] : ($this->args['attrs'] ?? []),
				$mandatory_attrs);

			echo sprintf('<label for="%2$s"><input %1$s /> %3$s</label>',
				Utils::build_attrs_from_array($attrs),
				$attrs['id'],
				$label);
			echo '<br>';
		}
		if (!empty($this->args['description'])) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}
		echo '</fieldset>';
	}

	private function render_radio_field() {
		echo '<fieldset>';
		foreach ($this->args['labels'] as $value => $label) {
			$mandatory_attrs = [
				'type'    => 'radio',
				'name'    => esc_attr($this->id),
				'value'   => esc_attr($value),
				'checked' => get_option($this->id, $this->default) === $value,
			];
			$default_attrs = [
				'id' => esc_attr($this->id) . '-' . esc_attr($value) . '-input',
			];
			$attrs = array_merge($default_attrs,
				is_array($this->args['attrs'][$value]) ? $this->args['attrs'][$value] : ($this->args['attrs'] ?? []),
				$mandatory_attrs);

			echo sprintf('<label for="%2$s"><input %1$s /> %3$s</label>',
				Utils::build_attrs_from_array($attrs),
				$attrs['id'],
				$label);
			echo '<br>';
		}
		if (!empty($this->args['description'])) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}
		echo '</fieldset>';
	}

	private function render_select_field() {
		$mandatory_attrs = [
			'name' => esc_attr($this->id),
		];
		$default_attrs = [
			'id' => esc_attr($this->id) . '-input',
		];
		$attrs = array_merge($default_attrs, $this->args['attrs'] ?? [], $mandatory_attrs);

		echo sprintf('<select %s>', Utils::build_attrs_from_array($attrs));
		foreach ($this->args['labels'] as $value => $label) {
			echo sprintf('<option value="%1$s"%3$s>%2$s</option>',
				esc_attr($value), $label, selected($value, get_option($this->id, $this->default), false));
		}
		echo '</select>';
		if (!empty($this->args['description'])) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}
	}
}

class SettingsSection {
	public $id;
	public $title;
	public $callback;
	public $args;

	public $settings;

	public function __construct(string $id, string $title, ?callable $callback, array $args) {
		$this->id = $id;
		$this->title = $title;
		$this->callback = $callback;
		$this->args = $args;
		$this->settings = [];
	}

	public function register(string $id, string $label, $type, $default, array $args = []) {
		$this->settings[] = new SettingsField($id, $label, $type, $default, $args);

		return $this;
	}

	public function register_callback(string $id, string $label, callable $callback, $default, array $args = []) {
		$this->settings[] = new SettingsField($id, $label, $callback, $default, $args);

		return $this;
	}
}

/**
 * Settings helper for the plugin and its dependents.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Settings {
	/**
	 * The settings slug to use.
	 * 
	 * @since 1.3.0
	 */
	public $slug;

	public $title;

	private $sections;

	private $sections_indices;

	public function __construct(string $slug, string $title) {
		$this->slug = $slug;
		$this->title = $title;
		$this->sections = [];
		$this->sections_indices = [];
	}

	public function __get(string $name) {
		return $this->get($name);
	}

	public function get(string $name) {
		return $this->sections[$this->sections_indices[$name]];
	}

	public function add_section(string $id, string $title, callable $callback = null, array $args = []): SettingsSection {
		$this->sections[] = new SettingsSection($id, $title, $callback, $args);
		$this->sections_indices[$id] = count($this->sections) - 1;

		return $this->get($id);
	}

	public function run() {
		foreach ($this->sections as $section) {
			add_settings_section($this->slug . '-' . $section->id, $section->title, $section->callback, $this->slug, $section->args);

			foreach ($section->settings as $setting) {
				register_setting($this->slug, $setting->id);
				add_settings_field(
					$setting->id . '-field',
					$setting->label,
					array($setting, 'render'),
					$this->slug,
					$this->slug . '-' . $section->id,
					['label_for' => $setting->label_for()]
				);

				if ($setting->is_dummy()) {
					add_filter("pre_update_option_{$setting->id}", function($value, $old_value) use ($setting) {
						$setting->_do_dummy();
						return $old_value;  // prevent an update, and hence prevent the option from even existing and clogging up the DB
					}, 10, 2);
				}
			}
		}
	}

	public function render() {
		echo '<div class="wrap">';
		echo '<h1>' . $this->title . '</h1>';
		echo '<form action="options.php" method="post">';
		
		settings_fields($this->slug);
		do_settings_sections($this->slug);

		echo '<button type="submit" name="submit" id="submit" class="button button-primary button-large">' . __('Save Changes') . '</button>';
		echo '</form>';
		echo '</div>';
	}

	public function render_screen() {
		add_options_page($this->title, $this->title, 'manage_options', $this->slug, array($this, 'render'));
	}
}
