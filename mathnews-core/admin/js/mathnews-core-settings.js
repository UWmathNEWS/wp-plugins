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
		const listener = () => {
			for (const { dependent, value } of dependents) {
				const disabled = value == 'on' || value == 'off' ?
					(value == 'on') == $dep.prop('checked') :
					(value[0] == '!' ? $dep.val() != value.slice(1) : $dep.val() == value);
				dependent.prop('disabled', disabled);

				if (dependent.data('editor-id')) {
					tinymce.get(dependent.data('editor-id')).setMode(disabled ? 'readonly' : 'design');
					dependent.css('opacity', disabled ? 0.5 : 1);
				}
			}
		};
		$dep.on('change', listener);
		listener();
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
	})
});
