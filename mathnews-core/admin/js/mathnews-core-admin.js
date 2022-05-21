jQuery(($) => {
	// Replace the submission meta box id so it can work with Wordpress's post.js
	$("#mn-submitdiv").attr("id", "submitdiv");

	// disable sorting for postscript meta box (since that messes with TinyMCE)
	if ($('#normal-sortables').sortable !== undefined) {
		$('#normal-sortables').sortable({
			disabled: true
		});
	}

	// port "Add Media" button for all contributors
	if ($('#wp-content-editor-tools').length && $('#wp-content-media-buttons').length === 0) {
		// create the "Add Media" button
		const button = $('<button>', {
			type: 'button',
			id: 'insert-media-button',
			class: 'button insert-media add_media',
		});
		button.html(`<span class="wp-media-buttons-icon"></span> Add Media`);
		// custom onclick code so that it opens only on the insert from URL frame
		button.on('click', (e) => {
			e.preventDefault();
			e.stopPropagation();
			let editor = 'content';
			let config = {
				frame: 'post',
				state: 'embed',
				title: window.wp.media.view.l10n.insertFromUrlTitle,
			};
			window.wp.media.editor.open(editor, config)
		});
		// this wrapper is necessary for the icons to work
		const button_wrap = $(`<div>`, {
			id: 'wp-content-media-buttons',
			class: 'wp-media-buttons',
		});
		button_wrap.append(button);
		$('#wp-content-editor-tools').prepend(button_wrap);

		// hide navigation to all frames that aren't the insert from URL frame
		$('head').append(`<style>.media-menu :not(#menu-item-embed) { display: none }</style>`)
	}

	// update tag pill in submit button on tag input changes
	if ($('#mn_publish-tag').length) {
		const tag_delimiter = window.wp.i18n._x( ',', 'tag delimiter' ) || ',';
		const tag_el = $('#mn_publish-tag');  // the tag pill
		const tag_textarea = $('#tax-input-post_tag');  // the hidden textarea where WordPress stores tags
		const cur_issue = $('#mn_publish-default-tag').text();  // the current issue default tag
		// this is the function that does the actual updating
		const update_tag_el = () => {
			let tags = tag_textarea.val().split(tag_delimiter).filter((tag) => tag !== '');
			if (tags.length === 0) {
				tags = [cur_issue];
			}
			const tag_inner = $('<code>');
			tag_inner.text(tags[0]);
			const additional_text = tags.length > 1 ? ', &hellip;' : '';
			tag_el.html(tag_inner).append(additional_text);
		};
		// hook into tag saving
		// quickClicks is the function that renders the tag list, and hence is run every time the tag list is updated
		// https://github.com/WordPress/WordPress/blob/270f2011f8ec7265c3f4ddce39c77ef5b496ed1c/wp-admin/js/tags-box.js#L124
		window.tagBox.__quickClicks = window.tagBox.quickClicks;
		window.tagBox.quickClicks = function() {
			update_tag_el();
			return window.tagBox.__quickClicks.apply(window.tagBox, arguments);
		};
		update_tag_el();
	}

	// add handlers for closing notification dialogs
	$('.notification-dialog-wrap').each((i, el) => {
		$(el).find('.dismiss-notification-dialog').on('click', () => {
			$(el).addClass('hidden');
		});
	});

	// do stuff related to editor locking
	if ($('#mn-editor-lock-warning').length) {
		// disable post lock dialog, because it's useless
		$('#post-lock-dialog').remove();
		$('#mn-editor-lock-warning .dismiss-notification-dialog').on('click', () => {
			// disable various editing elements
			const disabled_els = [
				'#title',  // title field
				'#mn-subtitle',  // subtitle field
				'#mn-author',  // author field
				'#edit-slug-buttons button',  // permalink field
				'#save-post',  // save draft button
				'#publish',  // publish button
				'#content',  // content textarea
				'.quicktags-toolbar .ed_button',  // content text mode buttons
				'#mn-postscript',  // postscript textarea
				'#qt_mn-postscript_toolbar .ed_button',  // postscript text mode buttons
				'#new-tag-post_tag',  // tag input field
				'.tagadd',  // tag add button
				'.ntdelbutton',  // tag delete button
			];
			$(disabled_els.join(',')).attr('disabled', true);
		});
	}

	// show rejection dialog on reject button click
	$('#mn-show-reject-dialog').on('click', () => {
		$('#mn-reject-dialog').removeClass('hidden');
	});

	// require the copyeditor to provide a rationale before rejection an article
	$('#mn-reject-rationale').on('input', (e) => {
		$('#mn-reject').attr('disabled', e.target.value.length === 0)
	});

	// add "publish new issue" button
	if (mn_core.isAdmin && $('.page-title-action').length && (mn_core.currentScreen === 'edit-post' || mn_core.currentScreen === 'post')) {
		$('.page-title-action').after(`<a href="/wp-admin/post-new.php?mn-publish=1" class="page-title-action">Publish New Issue</a>`);
	}

	// automatically categorize new published issues
	if (mn_core.currentScreen === 'post' && mn_core.isAdmin && location.search) {
		const params = new URLSearchParams(location.search);
		if (params.has('mn-publish')) {
			$('.wrap h1').text('Publish New Issue');  // make it clear what screen we're on
			$(`input#in-category-${mn_core.categories.BACKISSUE_CAT_NAME}`).attr('checked', true);
		}
	}
});
