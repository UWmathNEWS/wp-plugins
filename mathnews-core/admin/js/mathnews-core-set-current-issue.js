jQuery(($) => {
	const tag_el = $('#current-issue-tag');
	const volume_input = $('#current-issue-tag-volume');
	const issue_input = $('#current-issue-tag-issue');
	const current_tag = tag_el.text();
	const update_tag_el = () => {
		const volume_num = volume_input.val();
		const issue_num = issue_input.val();
		const new_tag = `v${volume_num}i${issue_num}`;
		tag_el.text(new_tag);

		$('#current-issue-tag-return').prop('disabled', new_tag === current_tag);
	};

	volume_input.on('keydown', (e) => {
		// allow for shortcode typing
		if (e.key === 'v' || e.key === 'i' || e.key === '.') {
			e.preventDefault();
			if (e.key === 'i' || e.key === '.') {
				// auto-tab to issue field
				issue_input.trigger('focus').trigger('select');
			}
			return;
		}
	});

	volume_input.on('input', (e) => {
		volume_num = e.target.value;
		update_tag_el();
	});

	issue_input.on('input', (e) => {
		issue_num = e.target.value;
		update_tag_el();
	});

	update_tag_el();
});
