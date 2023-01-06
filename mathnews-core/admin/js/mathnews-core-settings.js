jQuery(($) => {
	// Disable fields based on values of other fields
	let dependencies = {};
	$('[data-disabled-by]').each(function () {
		const [depends, depends_value] = $(this).data('disabled-by').split('::');
		if (dependencies.hasOwnProperty(depends)) {
			dependencies[depends].push({ dependent: $(this), value: depends_value });
		} else {
			dependencies[depends] = [{ dependent: $(this), value: depends_value }];
		}
	});

	for (const [dep, dependents] of Object.entries(dependencies)) {
		const $dep = $(dep);
		const checkDisabled = (value) => value == 'on' || value == 'off' ?
			(value == 'on') == $dep.prop('checked') :
			(value[0] == '!' ? $dep.val() != value.slice(1) : $dep.val() == value);
		const listener = () => {
			for (const { dependent, value } of dependents) {
				const disabled = checkDisabled(value);
				dependent.prop('readonly', disabled);

				if (dependent.data('editor-id')) {
					tinymce.get(dependent.data('editor-id'))?.setMode(disabled ? 'readonly' : 'design');
					dependent.find('.quicktags-toolbar input').prop('disabled', disabled);
					dependent.find('textarea').prop('readonly', disabled);
				}
			}
		};
		$dep.on('change', listener);
		listener();

		// if the page is loaded with the quicktags editor, TinyMCE won't be initialized and disabling it will fail. So we
		// have to hook into its initialization event to properly disable it.
		// NOTE: sometimes TinyMCE won't have disabled styling despite being readonly. It's an upstream issue.
		$(document).on('tinymce-editor-init', () => {
			dependents.filter(({ dependent }) => dependent.data('editor-id')).forEach(({ dependent, value }) => {
				tinymce.get(dependent.data('editor-id'))?.setMode(checkDisabled(value) ? 'readonly' : 'design');
			});
		});
	}

	// Show/hide passwords in password fields
	$('.mn-pwd-visibility-toggle').each(function() {
		const $el = $(this);
		const $field = $(document.getElementById($el.attr('aria-controls')));

		$el.on('click', () => {
			const show = $field.attr('type') === 'password';
			if (show) {
				$field.attr('type', 'text');
				$el.children('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
				$el.children('.text').text('Hide');
				$el.attr('aria-label', 'Hide password');
			} else {
				$field.attr('type', 'password');
				$el.children('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
				$el.children('.text').text('Show');
				$el.attr('aria-label', 'Show password');
			}
		});

		// Prevent browser autocomplete for password fields
		$el.closest('form').on('submit', () => {
			$field.attr('type', 'password');
		});
	});

	// Enable tabs
	$('.tab-container').on('click', (e) => {
		if (e.target.tagName !== "BUTTON") return;
		const $tab = $(e.target);
		const $panel = $(document.getElementById($tab.attr('aria-controls')));

		if ($tab.attr('aria-selected') === 'true') return;

		const url = new URL(window.location);
		url.searchParams.set('tab', $tab.attr('id'));
		window.history.replaceState({}, '', url);

		$tab
			.attr('aria-selected', 'true')
			.siblings('[aria-controls]')
			.attr('aria-selected', 'false');
		$panel
			.removeClass('hidden')
			.siblings('[role="tabpanel"]')
			.addClass('hidden');
	});
});
