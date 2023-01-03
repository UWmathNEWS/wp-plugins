<?php
/**
 * Settings helper for the plugin and its dependents.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.3.0
 *
 * @package    Mathnews\WP\Core
 */

namespace Mathnews\WP\Core;

use Mathnews\WP\Core\Consts;
use Mathnews\WP\Core\Utils;

/**
 * Class corresponding fields to a single option.
 *
 * @since 1.3.0
 */
class SettingsField {
	/**
	 * The name of the option this instance corresponds to.
	 *
	 * @since 1.3.0
	 */
	public $id;

	/**
	 * The label to print out for this option.
	 *
	 * @since 1.3.0
	 */
	public $label;

	private $callback;
	private $default;
	private $args;

	/**
	 * Construct a setting.
	 *
	 * @see SettingsSection::register
	 * @since 1.3.0
	 */
	public function __construct(string $id, string $label, $callback, $default, array $args) {
		$this->id = $id;
		$this->label = $label;
		$this->callback = $callback;
		$this->default = $default;
		$this->args = $args;
	}

	/**
	 * Returns if this field is a dummy field.
	 *
	 * @since 1.3.0
	 */
	public function is_dummy(): bool {
		return isset($this->args['dummy']) ? true : false;
	}

	/**
	 * Performs the dummy action, if any.
	 *
	 * @since 1.3.0
	 */
	public function _do_dummy(...$args) {
		if (is_callable($this->args['dummy'])) {
			call_user_func($this->args['dummy'], ...$args);
		}
	}

	/**
	 * If the field is to be assigned a label, returns the id of the input field. Otherwise, returns an empty string.
	 *
	 * @since 1.3.0
	 */
	public function label_for(): string {
		return in_array($this->callback, ['radio', 'checkbox']) || $this->args['no_label']
			? ''
			: esc_attr($this->args['id'] ?? ($this->id . '-input'));
	}

	/**
	 * Renders the field(s) corresponding to this option.
	 *
	 * @since 1.3.0
	 */
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

	// Renders a text input field.
	private function render_text_field() {
		// determine the type attribute, and default to text if invalid
		$field_type = $this->args['type'] ?? 'text';
		if (in_array($field_type, ['button', 'checkbox', 'file', 'image', 'radio', 'reset', 'submit'])) {
			$field_type = 'text';
		}

		// build attributes
		$mandatory_attrs = [
			'type'  => $field_type,
			'name'  => esc_attr($this->id),
			'value' => esc_attr(get_option($this->id, $this->default)),
		];
		$default_attrs = [
			'id'   => esc_attr($this->id) . '-input',
			'size' => 30,
		];
		$attrs = array_merge($default_attrs, $this->args['attrs'] ?? [], $mandatory_attrs);

		// disable autocomplete for password fields
		if ($field_type === 'password') {
			$attrs['autocomplete'] = 'off';
		}

		if (!empty($this->args['before_text'])) {
			echo wp_kses_post($this->args['before_text']);
		}

		echo sprintf('<input %s />', Utils::build_attrs_from_array($attrs));

		// show password hide/show
		if ($field_type === 'password') {
			$btn_attrs = [
				'type' => 'button',
				'class' => 'button mn-pwd-visibility-toggle wp-hide-pw hide-if-no-js',
				'aria-label' => 'Show password',
				'aria-controls' => $attrs['id'],
			];
			echo sprintf('<button %s>', Utils::build_attrs_from_array($btn_attrs));
			echo '<span class="dashicons dashicons-visibility" aria-hidden="true"></span> <span class="text">Show</span>';
			echo '</button>';
		}

		if (!empty($this->args['after_text'])) {
			echo wp_kses_post($this->args['after_text']);
		}

		if (!empty($this->args['description'])) {
			echo '<p class="description">' . wp_kses_post($this->args['description']) . '</p>';
		}
	}

	// Renders a textarea.
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
			echo '<p class="description">' . wp_kses_post($this->args['description']) . '</p>';
		}
	}

	// Renders a wp_editor.
	private function render_editor_field() {
		$mandatory_attrs = [];
		$default_attrs = [
			'id' => esc_attr($this->id) . '-input',
		];
		$attrs = array_merge($default_attrs, $this->args['attrs'] ?? [], $mandatory_attrs);

		$attrs['data-editor-id'] = $attrs['id'];
		$attrs['id'] = $attrs['id'] . '-wrapper';

		$args = array_merge([
			'media_buttons' => false,
			'teeny'         => true,
		], $this->args['editor'] ?? [], ['textarea_name' => $this->id]);

		echo sprintf('<div %s>', Utils::build_attrs_from_array($attrs));
		wp_editor(get_option($this->id, $this->default), $attrs['data-editor-id'], $args);
		echo '</div>';
		if (!empty($this->args['description'])) {
			echo '<p class="description">' . wp_kses_post($this->args['description']) . '</p>';
		}
	}

	// Renders a fieldset of checkboxes.
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
			echo '<p class="description">' . wp_kses_post($this->args['description']) . '</p>';
		}
		echo '</fieldset>';
	}

	// Renders a fieldset of radio buttons.
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
			echo '<p class="description">' . wp_kses_post($this->args['description']) . '</p>';
		}
		echo '</fieldset>';
	}

	// Renders a select field.
	private function render_select_field() {
		$mandatory_attrs = [
			'name' => esc_attr($this->id),
		];
		$default_attrs = [
			'id' => esc_attr($this->id) . '-input',
		];
		$attrs = array_merge($default_attrs, $this->args['attrs'] ?? [], $mandatory_attrs);

		if (!empty($this->args['before_text'])) {
			echo wp_kses_post($this->args['before_text']);
		}

		echo sprintf('<select %s>', Utils::build_attrs_from_array($attrs));
		foreach ($this->args['labels'] as $value => $label) {
			echo sprintf('<option value="%1$s"%3$s>%2$s</option>',
				esc_attr($value), $label, selected($value, get_option($this->id, $this->default), false));
		}
		echo '</select>';

		if (!empty($this->args['after_text'])) {
			echo wp_kses_post($this->args['after_text']);
		}

		if (!empty($this->args['description'])) {
			echo '<p class="description">' . wp_kses_post($this->args['description']) . '</p>';
		}
	}
}

class SettingsSection {
	public $id;
	public $title;
	public $callback;
	public $args;

	public $settings;

	/**
	 * Constructs a section.
	 *
	 * @see Settings::add_section
	 * @since 1.3.0
	 */
	public function __construct(string $id, string $title, ?callable $callback, array $args) {
		$this->id = $id;
		$this->title = $title;
		$this->callback = $callback;
		$this->args = $args;
		$this->settings = [];
	}

	/**
	 * Register fields for an option
	 *
	 * @param string $option The option to register fields for
	 * @param string $label A label to display for the option; shown in the left column of the settings screen
	 * @param mixed $callback The type of field to display. Can be one of `text`, `textarea`, `editor`, `checkbox`,
	 *                        `radio`, `select`, or a callable that echoes custom markup.
	 * @param mixed $default The default value of the option. If $callback is `checkbox`, this must be an array of either
	 *                       `off` or `on` corresponding to each displayed checkbox.
	 * @param array $args {
	 * 	Optional. Additional arguments to pass to $callback. For predefined fields, the following keys are recognized:
	 *
	 * 	@type string $after_text Content to output after the field. Only valid when $callback is `text` or `select`.
	 * 	@type array $attrs HTML attributes to assign to the field. `type`, `name`, `value`, and `checked` are ignored. If
	 * 	                   $callback is `checkbox` or `radio`, then this can be an array of arrays, where each entry in
	 * 	                   the top-level array corresponds to the specified item; specifically, for `radio` each
	 * 	                   top-level key must also be a key in $labels. Otherwise, $attrs is shared across all items.
	 * 	@type string $before_text Content to output before the field. Only valid when $callback is `text` or `select`.
	 * 	@type string $description HTML Description for the field.
	 * 	@type callable|bool $dummy Flag this as a dummy option (i.e. there is no corresponding DB entry). If a callable
	 * 	                           is supplied, it is passed to the `pre_update_option_{$option}` hook; the return value
	 * 	                           is ignored.
	 * 	@type array $editor Additional arguments to pass to wp_editor. Only valid when $callback is `editor`.
	 * 	@type array $labels Labels to display for each checkbox, radio, or select option. For the latter two, this must
	 * 	                    be an associative array with keys corresponding to the `value` attribute. Only valid when
	 * 	                    $callback is `checkbox`, `radio`, or `select`.
	 * 	@type bool $no_label If true, the `<label>` defined by $label does not refer to the input field.
	 * 	@type string $type For text inputs, the specific type of input (e.g. `email`, `password`, `date`). Only valid
	 * 	                   when $callback is `text`.
	 *
	 * }
	 *
	 * @since 1.3.0
	 */
	public function register(string $option, string $label, $callback, $default, array $args = []): SettingsSection {
		$this->settings[] = new SettingsField($option, $label, $callback, $default, $args);

		return $this;
	}
}

/**
 * Create a settings page. This is a wrapper around the WordPress Settings API.
 *
 * @package    Mathnews\WP\Core
 * @subpackage Mathnews\WP\Core\Admin
 * @author     mathNEWS Editors <mathnews@gmail.com>
 */
class Settings {
	/**
	 * The slug to use for the settings page.
	 * 
	 * @since 1.3.0
	 */
	public $slug;

	/**
	 * The title of the settings page.
	 *
	 * @since 1.3.0
	 */
	public $title;

	private $sections;

	private $sections_indices;

	private $tabs;

	/**
	 * Construct a settings page.
	 *
	 * @param string $slug The slug for the settings page.
	 * @param string $title The title of the settings page.
	 * @param bool $use_tabs Enable the tabbing interface. Default false.
	 *
	 * @since 1.3.0
	 * @since 1.4.0 Add $use_tabs parameter.
	 */
	public function __construct(string $slug, string $title, bool $use_tabs = false) {
		$this->slug = $slug;
		$this->title = $title;
		$this->sections = [];
		$this->sections_indices = [];
		$this->tabs = $use_tabs ? [] : null;
	}

	/**
	 * Get a section of the settings.
	 *
	 * @deprecated
	 * @see Settings::get
	 * @since 1.3.0
	 */
	public function __get(string $id): SettingsSection {
		return $this->get($id);
	}

	/**
	 * Get a section of the settings.
	 *
	 * @param string $id The id of the desired section
	 *
	 * @since 1.3.0
	 */
	public function get(string $id): SettingsSection {
		return $this->sections[$this->sections_indices[$id]];
	}

	/**
	 * Add a new tab in the settings page. Only valid if tabs are enabled.
	 *
	 * @param string $id Unique id to identify the tab.
	 * @param string $title Displayed tab title.
	 */
	public function add_tab(string $id, string $title): Settings {
		if (is_null($this->tabs)) {
			wp_die("Error: tried adding tab $id to settings screen $slug when tabs are not enabled.");
		}

		$this->tabs[$id] = [
			'title' => $title,
			'sections' => [],
		];

		return $this;
	}

	/**
	 * Add a new section to the settings page.
	 *
	 * @param string $id The id of the new section
	 * @param string $title The title of the new section
	 * @param array $args {
 	 * 	Optional. Extra arguments for the section. Uses parameters from add_settings_section(), plus the following:
 	 *
 	 * 	@type callable $callback Function that echos out any content at the top of the section.
 	 * 	@type array $tab The tab to assign this section to, if tabs are enabled. If tabs are enabled and this is not
 	 * 	                 specified, a new tab will be created with the section's $id and $title.
	 * }
	 *
	 * @since 1.3.0
	 * @since 1.4.0 Deprecated the $callback parameter and moved it into $args.
	 */
	public function add_section(string $id, string $title, $callback = null, array $args = []): SettingsSection {
		if ((is_null($callback) && count($args) > 0) || is_callable($callback)) {
			_deprecated_argument(__FUNCTION__, '1.4.0', 'The four-parameter version is deprecated; use the three-parameter version instead.');
		} else {
			$args = $callback ?? [];
			$callback = $args['callback'];
		}

		$this->sections[] = new SettingsSection($id, $title, $callback, $args);
		$this->sections_indices[$id] = count($this->sections) - 1;

		return $this->get($id);
	}

	/**
	 * Register all settings for this page with WordPress. Must be called after all options have been registered.
	 *
	 * @since 1.3.0
	 */
	public function run() {
		foreach ($this->sections as $ind => $section) {
			add_settings_section($section->id, $section->title, $section->callback, $this->slug, $section->args);

			foreach ($section->settings as $setting) {
				register_setting($this->slug, $setting->id);
				add_settings_field(
					$setting->id . '-field',
					$setting->label,
					array($setting, 'render'),
					$this->slug,
					$section->id,
					['label_for' => $setting->label_for()]
				);

				if ($setting->is_dummy()) {
					add_filter("pre_update_option_{$setting->id}", function($value, $old_value, $option) use ($setting) {
						$setting->_do_dummy($value, $old_value, $option);
						return $old_value;  // prevent an update, and hence prevent the option from even existing and clogging up the DB
					}, 10, 3);
				}
			}

			if (isset($section->args['tab']) && !is_null($this->tabs) && isset($this->tabs[$section->args['tab']])) {
				$this->tabs[$section->args['tab']]['sections'][] = $ind;
			} else if (!is_null($this->tabs)) {
				$this->add_tab($section->id, $title);
				$this->tabs[$section->id]['sections'][] = $ind;
			}
		}
	}

	/**
	 * Render the settings page. Should not be called by itself.
	 *
	 * @see Settings::render_screen
	 * @since 1.3.0
	 */
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

	/**
	 * Render the settings page with tabs. Should not be called by itself.
	 *
	 * @see Settings::render_screen
	 * @since 1.4.0
	 */
	public function render_with_tabs() {
		?>
<div class="wrap">
	<h1><?php echo $this->title; ?></h1>
	<div class="tab-container" role="tablist">
		<?php
		foreach ($this->tabs as $id => $tab) {
			echo sprintf('<button id="%1$s" role="tab" aria-selected="%3$s" aria-controls="panel-%1$s">%2$s</button>',
				esc_attr($id),
				esc_html($tab['title']),
				array_key_first($this->tabs) === $id ? 'true' : 'false');
		}
		?>
	</div>
	<form action="options.php" method="post">
		<?php
		settings_fields($this->slug);

		foreach ($this->tabs as $id => $tab) {
			echo sprintf('<section id="panel-%s" class="%s" role="tabpanel" aria-labelledby="%1$s">',
				esc_attr($id),
				array_key_first($this->tabs) === $id ? '' : 'hidden');

			foreach($tab['sections'] as $ind) {
				$section = $this->sections[$ind];

				if (isset($section->args['before_section'])) {
					echo wp_kses_post(sprintf($section->args['before_section'], esc_attr($section->args['section_class'] ?? '')));
				}

				if ($section->title) {
					echo '<h2>' . $section->title . '</h2>';
				}

				if (!is_null($section->callback)) {
					call_user_func($section->callback, array_merge(['id' => $section->id, 'title' => $section->title], $section->args));
				}

				echo '<table class="form-table" role="presentation">';
				do_settings_fields($this->slug, $section->id);
				echo '</table>';

				if (isset($section->args['after_section'])) {
					echo wp_kses_post($section->args['after_section']);
				}
			}

			echo '</section>';
		}
		?>
		<button type="submit" name="submit" id="submit" class="button button-primary button-large"><?php echo __('Save Changes'); ?></button>
	</form>
</div>
		<?php
	}

	/**
	 * Register the settings page.
	 *
	 * @param callable $callback A function to render the settings page. If not supplied, will use Settings::render(), or
	 *                           Settings::render_with_tabs() if tabs are enabled,
	 *
	 * @since 1.3.0
	 */
	public function render_screen(?callable $callback = null) {
		if (is_null($callback)) {
			$callback = is_null($this->tabs) ? array($this, 'render') : array($this, 'render_with_tabs');
		}
		add_options_page($this->title, $this->title, 'manage_options', $this->slug, $callback);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 0);
	}

	/**
	 * Enqueue common scripts and styles for the settings page.
	 *
	 * @param string $base_screen The slug for the base screen this page is on. Used to identify which page to load
	 *                            scripts and styles on.
	 *
	 * @since 1.4.0
	 */
	public function enqueue_scripts(string $base_screen = 'settings_page_') {
		if (get_current_screen()->base === $base_screen . $this->slug) {
			// Scripts
			wp_enqueue_script(PLUGIN_NAME . '-settings',
				plugin_dir_url(dirname(__FILE__)) . 'admin/js/mathnews-core-settings.js', ['jquery', 'wp-tinymce', 'quicktags'], VERSION, true);

			// Styles
			wp_enqueue_style(PLUGIN_NAME . '-settings',
				plugin_dir_url(dirname(__FILE__)) . 'admin/css/mathnews-core-settings.css', [], VERSION, 'all');

			/**
			 * Enqueue additional scripts and styles for a settings page.
			 *
			 * @since 1.4.0
			 */
			do_action("mn_settings_enqueue_scripts_{$this->slug}");
		}
	}
}
