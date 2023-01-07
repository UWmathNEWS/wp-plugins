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
		loadingMore: false,  // is the app loading more entries?
		data: window.mn_audit_ui.data,  // log entries, initialized by the backend
		filtersMap: window.mn_audit_ui.filters,  // map of filter keys => display name
		filters: getFiltersFromQuery(),  // applied filters, generated from location.search
		actors: window.mn_audit_ui.users,  // list of actors
		icons: {  // map of units/verbs to icons
			cur_issue: 'tag',
			page: 'admin-page',
			plugin: 'admin-plugins',
			post: 'text-page',
			settings: 'admin-generic',
			user: 'admin-users',
			approve: 'yes',
			reject: 'no',
			create: 'plus',
			delete: 'trash',
			update: 'update',
		},
		// apply filters
		updateFilters(filter, value) {
			if (!value) {
				delete this.filters[filter];
			} else {
				this.filters[filter] = value;
			}
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

			this.fetch(this.filters).then(json => {
				this.data = json.data ?? { entries: [] };
				this.loading = false;
			});
		},
		// load more entries
		loadMore() {
			const { log_id } = this.data.entries[this.data.entries.length - 1];
			this.loadingMore = true;
			this.fetch({ ...this.filters, log_id }).then(({ data }) => {
				this.data.entries.push(...data.entries);
				this.data.more = data.more;
				this.loadingMore = false;
			});
		},
		// fetch entries
		fetch(filters) {
			const params = {
				...filters,
				'action': 'mn_audit_filter',
				'_ajax_nonce': window.mn_audit_ui.nonce
			};
			return fetch(`${window.mn_core.ajaxurl}?${new URLSearchParams(params)}`)
				.then(resp => resp.json());
		}
	});

	const Entry = (props) => ({
		...props.entry,
		$template: '#mn-audit-log-entry',
		get log_action_stem() {
			return `${this.log_unit}.${this.log_verb}`;
		},
	});

	createApp({
		store,
		Entry
	}).mount('#mn-audit-log-mount');
})(window.PetiteVue);
