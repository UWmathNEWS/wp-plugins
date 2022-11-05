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
	private string $id;
	private string $label;
	private string | callable $callback;
	private mixed $default;
	private array $args;

	public function __construct(string $id, string $label, string | callable $callback, mixed $default, array $args) {
		$this->id = $id;
		$this->label = $label;
		$this->callback = $callback;
		$this->default = $default;
		$this->args = $args;
	}

	public function register($slug) {
		register_setting($slug, $this->id);
		add_settings_field(
			$this->id . '-field',
			$this->label,
			array($this, 'render'),
			$slug,
			$slug . '-' . $section->id,
			['label_for' => $this->id . '-input']
		);
	}

	public function render() {
		if ($this->callback === 'text') {
			render_text_field();
		} elseif ($this->callback === 'textarea') {
			render_textarea_field();
		} elseif ($this->callback === 'checkbox') {
			render_checkbox_field();
		} elseif ($this->callback === 'radio') {
			render_radio_field();
		} elseif (is_callable($this->callback)) {
			call_user_func($this->callback, $id, $default, $args);
		} else {
			wp_die('Non-recognized callback provided');
		}
	}

	private function render_text_field() {
		echo sprintf('<input type="text" id="%1$s-input" name="%1$s" value="%2$s" size="30" />',
			esc_attr($this->id), esc_attr(get_option($option_name, $default)));
		if (!empty($args['description'])) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	private function render_textarea_field() {
		echo sprintf('<textarea id="%1$s-input" name="%1$s" class="large-text">%2$s</textarea>',
			esc_attr($this->id), esc_attr(get_option($option_name, $default)));
		if (!empty($args['description'])) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	private function render_checkbox_field() {
		echo sprintf('<input type="checkbox" id="%1$s-input" name="%1$s"%2$s size="30" />',
			esc_attr($this->id), checked(get_option($option_name, $default), true, false));
		if (!empty($args['description'])) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	private function render_radio_field() {
		foreach ($args['values'] as $ind => $value) {
			echo '<div>';
			echo sprintf('<input type="checkbox" id="%2$s-input" name="%1$s" value="%2$s"%3$s size="30" />',
				esc_attr($this->id), esc_attr($value), checked($value, get_option($option_name, $default), false));
			echo sprintf('<label for="%1$s-input">%2$s</label>',
				esc_attr($value), $args['labels'][$ind]);
			echo '</div>';
		}
		if (!empty($args['description'])) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}
}

class SettingsSection {
	public string $id;
	public string $title;
	public callable $callback;
	public array $args;

	public array $settings;

	public function __construct(string $id, string $title, callable $callback, array $args) {
		$this->id = $id;
		$this->title = $title;
		$this->callback = $callback;
		$this->args = $args;
	}

	public function register(string $id, string $label, string $type, mixed $default, array $args = []) {
		$this->settings[] = new SettingsField($id, $label, $type, $default, $args);
	}

	public function register_callback(string $id, string $label, callable $callback, mixed $default, array $args = []) {
		$this->settings[] = new SettingsField($id, $label, $callback, $default, $args);
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
	public string $slug;

	public string $title;

	private array $sections;

	private array $sections_indices;

	public function __construct(string $slug, string $title) {
		$this->slug = $slug;
		$this->title = $title;
		$this->callback = $callback;
	}

	public function __get(string $name) {
		if (isset($this->sections_indices[$name])) {
			return $this->sections[$this->sections_indices[$name]];
		}
		return null;
	}

	public function add_section(string $id, string $title, callable $callback = false, array $args = []) {
		$this->sections[] = new SettingsSection($id, $title, $callback, $args);
		$this->sections_indices[$id] = count($this->sections) - 1;
	}

	public function run() {
		foreach ($this->sections as $section) {
			add_settings_section($this->slug . '-' . $section->id, $section->title, $section->callback, $this->slug, $section->args);

			foreach ($section->settings as $setting) {
				$setting->register($this->slug);
			}
		}
	}

	public function render() {
		echo '<div class="wrap">';
		echo '<h1>' . $this->title . '</h1>';
		echo '<form action="options.php" method="post">';
		
		settings_fields($this->slug);
		do_settings_sections($this->slug);

		echo '<button type="submit" name="submit" id="submit" class="button button-primary button-large">' . _('Save Changes') . '</button>';
		echo '</form>';
		echo '</div>';
	}
}