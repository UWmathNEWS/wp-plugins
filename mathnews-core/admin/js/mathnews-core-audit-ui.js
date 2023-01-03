(({ createApp, reactive }) => {
	const getFiltersFromQuery = () => {
		let queryFilters = new URLSearchParams(window.location.search);
		let filters = {};
		for (const [key, val] of queryFilters.entries()) {
			if ((key === 'log_actor_id' || key === 'log_action') && !!val) {
				filters[key] = val;
			}
		}
		return filters;
	};

	const store = reactive({
		loading: false,  // is the app loading?
		entries: window.mn_audit_ui.entries,  // log entries, initialized by the backend
		filtersMap: window.mn_audit_ui.filters,  // map of filter keys => display name
		filters: getFiltersFromQuery(),  // applied filters, generated from location.search
		actors: window.mn_audit_ui.users,  // list of actors
		icons: {  // map of units/verbs to icons
			post: 'text-page',
			cur_issue: 'tag',
			plugin: 'admin-plugins',
			user: 'admin-users',
			approve: 'yes',
			reject: 'no',
			create: 'plus',
			delete: 'trash',
			update: 'update',
		},
		// apply filters
		updateFilters(filter, value) {
			this.filters[filter] = value;
			this.populate();
		},
		// clear all applied filters
		clearFilters() {
			this.filters = {};
			this.populate();
		},
		// populate the log entries
		populate() {
			this.loading = true;

			// construct permalink and place in address bar
			const url = new URL(window.location.origin + window.location.pathname);
			url.searchParams.set('page', 'mn-audit-log');
			for (const filter of Object.entries(this.filters)) {
				url.searchParams.set(...filter);
			}
			window.history.replaceState({}, '', url);

			// fetch filtered log entries
			const params = {
				...this.filters,
				'action': 'mn_audit_filter',
				'_ajax_nonce': window.mn_audit_ui.nonce
			};
			fetch(`${window.mn_core.ajaxurl}?${new URLSearchParams(params)}`, {
				method: 'GET',
			})
				.then(resp => resp.json())
				.then(json => {
					this.entries = json.data ?? [];
					this.loading = false;
				});
		}
	});

	const Entry = (props) => ({
		...props.entry,
		$template: '#mn-audit-log-entry'
	});

	createApp({
		store,
		Entry
	}).mount('#mn-audit-log-mount');
})(window.PetiteVue);
