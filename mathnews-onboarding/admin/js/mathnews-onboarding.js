(($) => {
	const backButton = {
		action() {
			return this.back();
		},
		secondary: true,
		text: 'Back',
	};
	const nextButton = {
		action() {
			return this.next();
		},
		text: 'Next',
	};

	const tour = new Shepherd.Tour({
		confirmCancel: true,
		confirmCancelMessage: `Are you sure you want to exit the tour? It's pretty important.`,
		defaultStepOptions: {
			cancelIcon: { enabled: false },
			buttons: [ backButton, nextButton ],
			popperOptions: {
				modifiers: [
					{
						name: 'offset',
						options: {
							offset: [0, 20],
						},
					},
				],
			},
		},
		exitOnEsc: false,
		useModalOverlay: true,
	});

	// Introduction modal
	tour.addStep({
		id: 'intro',
		title: `Welcome to the mathNEWS article submission experience&copy;&reg;&trade;.`,
		text: `<p>Whether you're new to writing for mathNEWS, or you're a salty old writer who's done this for years, there's lots of new stuff to cover in this update.</p>
<p>So why don't we take a look?</p>`,
		buttons: [
			{
				action() {
					return this.next();
				},
				text: 'Start tour',
			}
		],
	});

	// Show the subtitle
	tour.addStep({
		id: 'subtitle',
		text: `<p>If you want to give your article a subtitle, you can now put it here instead of awkwardly putting a "SUBTITLE:" somewhere in your article.</p>`,
		attachTo: {
			element: '#mn-subtitle',
			on: 'auto',
		},
	});

	// Show the author
	tour.addStep({
		id: 'author',
		text: `<p>Gone are the days of forgetting to put in your author pseudonym (and suffering the wrathful consequences thereof).
Now, you can hide behind your own personal brand of anonymity right here.</p>
<p><strong>Tip:</strong> The pseudonym defaults to your account nickname, which you can change by <a href="/wp-admin/profile.php" target="_blank">editing your profile</a>.</p>`,
		attachTo: {
			element: '#mn-authorwrap',
			on: 'auto',
		},
	});

	// Show the postscript
	tour.addStep({
		id: 'postscript',
		text: `<p>If you need to add footnotes or a postscript to your article, we've given you a dedicated space to do so.
We advise you to use it sparingly, as too long a postscript can make an article unwieldy.</p>`,
		attachTo: {
			element: '#mn-postscriptdiv',
			on: 'auto',
		},
		scrollTo: { behavior: 'smooth', block: 'center' },
		beforeShowPromise() {
			return new Promise((resolve) => {
				$('#mn-postscriptdiv').removeClass('closed');
				resolve();
			});
		},
		when: {
			hide() {
				$('#mn-postscriptdiv').addClass('closed');
			},
		},
	});

	// Show the tags
	tour.addStep({
		id: 'tags',
		text: `<p>If you want to put your article in a specific issue, you can tag it with the desired issue(s) here.
Write the tag in the format <code>v[volume]i[issue]</code>, e.g. <code>${mn_core.currentIssue}</code> for the upcoming issue.</p>`,
		attachTo: {
			element: '#tagsdiv-post_tag',
			on: 'auto',
		},
		scrollTo: { behavior: 'smooth', block: 'center' },
	});

	if (mn_core.isAdmin) {
		// Tour the current issue page
		tour.addStep({
			id: 'set-current-issue',
			text: `<p>Speaking of tags, as an admin you can set the default tag for submissions by setting the upcoming issue's volume and issue number here.
Make sure to update this before every prod night.</p>`,
			attachTo: {
				element: '#menu-posts .wp-submenu a[href$="page=set-current-issue"]',
				on: 'auto',
			},
			canClickTarget: false,
			scrollTo: { behavior: 'smooth', block: 'center' },
			when: {
				show() {
					$('#menu-posts .wp-submenu a[href$="page=set-current-issue"]').parent().addClass('current');
				},
				hide() {
					$('#menu-posts .wp-submenu a[href$="page=set-current-issue"]').parent().removeClass('current');
				},
			},
		});
	}

	// Show the save draft button
	tour.addStep({
		id: 'save',
		text: `<p>Sometimes, s**t just happens, like that time the server had a harddrive failure right before prod night.
To prevent s**t from happening to you, save your work frequently here.</p>`,
		attachTo: {
			element: '#save-post',
			on: 'auto',
		},
		canClickTarget: false,
		scrollTo: { behavior: 'smooth', block: 'center' },
	});

	if (mn_core.isCopyeditor) {
		if ($('#mn-reject').length) {
			// Tour the mark editor okayed and reject button
			tour.addStep({
				id: 'approve',
				text: `<p>Instead of marking an article as 'Editor okayed' by jankily changing its categories, you can now approve articles for publishing with the click of a button.
Just try not to use this for your own articles.</p>`,
				attachTo: {
					element: '#publish',
					on: 'bottom-start',
				},
				canClickTarget: false,
				scrollTo: { behavior: 'smooth', block: 'center' },
			});
			tour.addStep({
				id: 'reject',
				text: `<p>You can also reject an article with the click of a button right here.
Again, try not to let this power go to your head, because you <em>will</em> have to provide a reason for it.</p>`,
				attachTo: {
					element: '#mn-show-reject-dialog',
					on: 'auto',
				},
				canClickTarget: false,
				scrollTo: { behavior: 'smooth', block: 'center' },
			});
		} else {
			// Own post, show the submit button
			tour.addStep({
				id: 'submit',
				text: `<p>When you're done writing your article, submit it for copyediting here.
After that, you'll see "Reject" and "Mark Editor Okayed" buttons in the place of the submit button.
As their names suggest, these buttons allow you to reject and approve an article for publishing respectively.</p>`,
				attachTo: {
					element: '#publish',
					on: 'bottom-start',
				},
				canClickTarget: false,
				scrollTo: { behavior: 'smooth', block: 'center' },
			});
		}
	} else {
		// Show the submit button
		tour.addStep({
			id: 'submit',
			text: `<p>When you're done writing your article, submit it here to let us know it's ready for publishing.
Make sure the issue listed is the one you want to submit your article for!</p>
<p><strong>Note:</strong> Once you submit your article, you won't be able to make any changes to it.</p>`,
			attachTo: {
				element: '#publish',
				on: 'bottom-start',
			},
			canClickTarget: false,
			scrollTo: { behavior: 'smooth', block: 'center' },
		});
	}

	// Ending screen
	tour.addStep({
		id: 'end',
		text: `<p>That's all for this tour. If you haven't already, check out the <a href="https://mathnews.notion.site/How-to-write-a-mathNEWS-article-a3cd29a0637245678fe21451e9a42d68" target="_blank" rel="noreferer nofollow noopener">writer's guide</a> for more tips and tricks.</p>
<p>Happy writing!</p>`,
		buttons: [
			{
				action() {
					return this.show(0);
				},
				secondary: true,
				text: 'Start over',
			},
			{
				action() {
					return this.next();
				},
				text: 'Finish tour',
			}
		],
		when: {
			show() {
				window.scrollTo({
					top: 0,
					left: 0,
					behavior: 'smooth',
				});
			},
		},
	});

	// Tell WP onboarding is complete when done
	tour.on('complete', () => {
		$.post(
			mn_core.ajaxurl,
			{
				action: 'mn_register_onboarding',
				_ajax_nonce: mn_onboarding.nonce,
			}
		);
	});

	tour.start();

})(jQuery);
