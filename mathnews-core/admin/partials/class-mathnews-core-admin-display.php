<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews_Core
 * @subpackage Mathnews_Core/admin/partials
 */

namespace Mathnews\WP\Core\Admin\Partials;

use Mathnews\WP\Core\Consts;

class Display {
	/**
	 * Renders a screen to set the current issue
	 *
	 * @since 1.0.0
	 */
	static public function render_current_issue_settings_screen() {
		$cur_issue = get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT);  // [volume_num, issue_num]

		?>
<div class="wrap">
    <h1><?php _e('Set Current Issue', 'textdomain'); ?></h1>
    <?php settings_errors(); ?>
    <form action="options.php" method="post">
        <?php
        settings_fields(Consts\CURRENT_ISSUE_SETTINGS_SLUG);
        do_settings_sections(Consts\CURRENT_ISSUE_SETTINGS_SLUG);
        ?>
        <button type="submit" name="submit" id="submit" class="button button-primary button-large">
            Set current issue tag to
            <code><span id="current-issue-tag"><?php echo esc_html("{$cur_issue[0]}i{$cur_issue[1]}"); ?></span></code>
        </button>
    </form>
</div>
		<?php
	}

	/**
	 * Renders description for setting the current issue
	 *
	 * @since 1.0.0
	 */
	static public function render_current_issue_settings_description() {
		?>
<p>
    Enter the volume and issue number for the upcoming issue.
    This will set the default tag to be applied when a writer submits an article.
</p>
		<?php
	}

	/**
	 * Renders fields to set the current issue
	 *
	 * @since 1.0.0
	 */
	static public function render_current_issue_settings_fields() {
		$option_name = Consts\CURRENT_ISSUE_OPTION_NAME;  // for brevity
		$cur_issue = get_option($option_name, Consts\CURRENT_ISSUE_OPTION_DEFAULT);  // [volume_num, issue_num]
		?>
<label for="current-issue-tag-volume">Volume</label>
<input type="text" id="current-issue-tag-volume" name="<?php echo esc_attr($option_name); ?>[0]" value="<?php echo esc_attr($cur_issue[0]); ?>" size="3" />
<label for="current-issue-tag-issue">Issue</label>
<input type="text" id="current-issue-tag-issue" name="<?php echo esc_attr($option_name); ?>[1]" value="<?php echo esc_attr($cur_issue[1]); ?>" size="1" />
		<?php
	}

	/**
	 * Generic helper for rendering notification dialogs
	 *
	 * @param $id ID to assign to the notification dialog
	 * @param $title Title of the notification dialog
	 * @param $callback Callback to render the notification dialog content
     * @param $hidden Should dialog be hidden?
	 * @param $args Additional arguments to pass to the callback function
	 *
	 * @since 1.0.0
	 */
	static public function notification_dialog($id, $title, $callback, $hidden = true, ...$args) {
		$safe_id = esc_attr($id);
		?>
<div id="<?php echo $safe_id; ?>" class="notification-dialog-wrap <?php echo ($hidden ? 'hidden' : ''); ?>">
    <div class="notification-dialog-background"></div>
    <div class="notification-dialog">
        <div id="<?php echo $safe_id; ?>--content" class="notification-dialog-content">
            <?php
            if ($title !== ''):
            ?>
                <h1 id="<?php echo $safe_id; ?>--title"><?php echo esc_html($title); ?></h1>
            <?php
            endif;
            ?>
            <?php call_user_func_array($callback, $args); ?>
        </div>
    </div>
</div>
		<?php
	}

	/**
	 * Renders the editor lock warning modal.
	 *
	 * @since 1.0.0
	 */
	static public function render_editor_lock_dialog() {
		?>
<div id="mn-editor-lock-warning--message">
    <p><?php _e('If you have any changes you would like to make, please contact the editors.', 'textdomain'); ?></p>
</div>
<p>
    <a class="button button-primary" href="<?php echo admin_url('edit.php'); ?>"><?php _e('Go back', 'textdomain'); ?></a>
    <button type="button" class="button dismiss-notification-dialog"><?php _e('View post', 'textdomain'); ?></button>
</p>
		<?php
	}

	/**
	 * Render a textarea when rejecting an article
	 *
	 * @since 1.0.0
	 */
	static public function render_rejection_dialog($reject_rationale) {
		?>
<div>
    <p>Please state why this article is being rejected.</p>
    <textarea id="mn-reject-rationale" name="mn-reject-rationale" class="widefat" rows="10"><?php echo esc_html($reject_rationale); ?></textarea>
</div>
<div>
    <p style="float:left">
        <button type="button" class="button dismiss-notification-dialog"><?php _e('Cancel', 'textdomain'); ?></button>
    </p>
    <p style="float:right">
        <?php submit_button( __( 'Reject', 'textdomain' ), 'submitdelete', 'mn-reject', false, strlen($reject_rationale) === 0 ? 'disabled' : '' ); ?>
    </p>
</div>
		<?php
	}

	/**
	 * Renders a subtitle input field
	 *
	 * @since 1.0.0
	 */
	static public function subtitle_input($subtitle, $can_edit_post) {
		?>
<div id="mn-subtitlewrap">
    <input type="text" name="mn_subtitle" size="30" value="<?php echo esc_attr( $subtitle ); ?>" id="mn-subtitle" spellcheck="true" autocomplete="off"
        placeholder="<?php _e('Subtitle (optional)', 'textdomain') ?>" <?php echo ($can_edit_post ? '' : 'disabled'); ?> />
</div>
		<?php
	}

	/**
	 * Renders a feedback admin notice
	 *
	 * @since 1.0.0
	 */
	static public function feedback_notice() {
		?>
<div class="notice notice-info">
    <p>
        We&#39;re testing out some changes to the article submission interface. Let us know what you think by emailing <a href="mailto:mathnews@gmail.com">mathnews@gmail.com</a>!
    </p>
</div>
		<?php
	}

	/**
	 * Renders our custom publish meta box
	 *
	 * @since 1.0.0
	 */
	static public function render_publish_meta_box($post, $nonce_field, $can_edit_post) {
		global $action;

		$post_id          = (int) $post->ID;
		$post_type        = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		$can_publish      = current_user_can('edit_others_posts');
        $user_is_author   = $post->post_author == get_current_user_id();  // double-equals as WP_Post::post_author is a numeric string

		?>
<div class="submitbox" id="submitpost">

<?php echo $nonce_field; ?>

<div id="minor-publishing">

    <?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key. ?>
    <div style="display:none;">
        <?php submit_button( __( 'Save' ), '', 'save' ); ?>
    </div>

    <div id="minor-publishing-actions">
        <div id="save-action">
            <?php
            if ( ! in_array( $post->post_status, array( 'publish', 'future', 'pending' ), true ) ) {
                $private_style = '';
                if ( 'private' === $post->post_status ) {
                    $private_style = 'style="display:none"';
                }
                ?>
                <input <?php echo $private_style; ?> type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save Draft' ); ?>" class="button" <?php echo ($can_edit_post ? '' : 'disabled '); ?> />
                <span class="spinner"></span>
            <?php } elseif ( $can_edit_post ) { ?>
                <input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save as Pending' ); ?>" class="button" />
                <span class="spinner"></span>
            <?php } ?>
        </div>

        <?php
        if ( is_post_type_viewable( $post_type_object ) ) :
            ?>
            <div id="preview-action">
                <?php
                $preview_link = esc_url( get_preview_post_link( $post ) );
                if ( 'publish' === $post->post_status ) {
                    $preview_button_text = __( 'Preview Changes' );
                } else {
                    $preview_button_text = __( 'Preview' );
                }

                $preview_button = sprintf(
                    '%1$s<span class="screen-reader-text"> %2$s</span>',
                    $preview_button_text,
                    /* translators: Accessibility text. */
                    __( '(opens in a new tab)' )
                );
                ?>
                <a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview-<?php echo $post_id; ?>" id="post-preview"><?php echo $preview_button; ?></a>
                <input type="hidden" name="wp-preview" id="wp-preview" value="" />
            </div>
            <?php
        endif;

        /**
         * Fires after the Save Draft (or Save as Pending) and Preview (or Preview Changes) buttons
         * in the Publish meta box.
         *
         * @since 4.4.0
         *
         * @param WP_Post $post WP_Post object for the current post.
         */
        do_action( 'post_submitbox_minor_actions', $post );
        ?>
        <div class="clear"></div>
    </div>

    <div id="misc-publishing-actions">
        <div class="misc-pub-section misc-pub-post-status">
            <?php _e( 'Status:' ); ?>
            <span id="post-status-display">
                <?php
                switch ( $post->post_status ) {
                    case 'private':
                        _e( 'Privately Published' );
                        break;
                    case 'publish':
                        _e( 'Published' );
                        break;
                    case 'future':
                        _e( 'Scheduled' );
                        break;
                    case 'pending':
                        _e( 'Pending Review' );
                        break;
                    case 'draft':
                    case 'auto-draft':
                        _e( 'Draft' );
                        break;
                }
                ?>
            </span>

            <?php
            if ( 'publish' === $post->post_status || 'private' === $post->post_status || $can_publish ) {
                $private_style = '';
                if ( 'private' === $post->post_status ) {
                    $private_style = 'style="display:none"';
                }
                ?>
                <a href="#post_status" <?php echo $private_style; ?> class="edit-post-status hide-if-no-js" role="button"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit status' ); ?></span></a>

                <div id="post-status-select" class="hide-if-js">
                    <input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ( 'auto-draft' === $post->post_status ) ? 'draft' : $post->post_status ); ?>" />
                    <label for="post_status" class="screen-reader-text"><?php _e( 'Set status' ); ?></label>
                    <select name="post_status" id="post_status">
                        <?php if ( 'publish' === $post->post_status ) : ?>
                            <option<?php selected( $post->post_status, 'publish' ); ?> value='publish'><?php _e( 'Published' ); ?></option>
                        <?php elseif ( 'private' === $post->post_status ) : ?>
                            <option<?php selected( $post->post_status, 'private' ); ?> value='publish'><?php _e( 'Privately Published' ); ?></option>
                        <?php elseif ( 'future' === $post->post_status ) : ?>
                            <option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php _e( 'Scheduled' ); ?></option>
                        <?php endif; ?>
                            <option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e( 'Pending Review' ); ?></option>
                        <?php if ( 'auto-draft' === $post->post_status ) : ?>
                            <option<?php selected( $post->post_status, 'auto-draft' ); ?> value='draft'><?php _e( 'Draft' ); ?></option>
                        <?php else : ?>
                            <option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e( 'Draft' ); ?></option>
                        <?php endif; ?>
                    </select>
                    <a href="#post_status" class="save-post-status hide-if-no-js button"><?php _e( 'OK' ); ?></a>
                    <a href="#post_status" class="cancel-post-status hide-if-no-js button-cancel"><?php _e( 'Cancel' ); ?></a>
                </div>
                <?php
            }
            ?>
        </div>

        <?php
        if ( ! empty( $args['args']['revisions_count'] ) ) :
            ?>
            <div class="misc-pub-section misc-pub-revisions">
                <?php
                /* translators: Post revisions heading. %s: The number of available revisions. */
                printf( __( 'Revisions: %s' ), '<b>' . number_format_i18n( $args['args']['revisions_count'] ) . '</b>' );
                ?>
                <a class="hide-if-no-js" href="<?php echo esc_url( get_edit_post_link( $args['args']['revision_id'] ) ); ?>"><span aria-hidden="true"><?php _ex( 'Browse', 'revisions' ); ?></span> <span class="screen-reader-text"><?php _e( 'Browse revisions' ); ?></span></a>
            </div>
            <?php
        endif;

        if ( 'draft' === $post->post_status && get_post_meta( $post_id, '_customize_changeset_uuid', true ) ) :
            ?>
            <div class="notice notice-info notice-alt inline">
                <p>
                    <?php
                    printf(
                        /* translators: %s: URL to the Customizer. */
                        __( 'This draft comes from your <a href="%s">unpublished customization changes</a>. You can edit, but there&#8217;s no need to publish now. It will be published automatically with those changes.' ),
                        esc_url(
                            add_query_arg(
                                'changeset_uuid',
                                rawurlencode( get_post_meta( $post_id, '_customize_changeset_uuid', true ) ),
                                admin_url( 'customize.php' )
                            )
                        )
                    );
                    ?>
                </p>
            </div>
            <?php
        endif;

        /**
         * Fires after the post time/date setting in the Publish meta box.
         *
         * @since 2.9.0
         * @since 4.4.0 Added the `$post` parameter.
         *
         * @param WP_Post $post WP_Post object for the current post.
         */
        do_action( 'post_submitbox_misc_actions', $post );
        ?>
    </div>

    <div class="clear"></div>
</div>

<div id="major-publishing-actions">
    <?php
    $author_pseudonym = get_post_meta($post->ID, Consts\AUTHOR_META_KEY_NAME, true);

    if ($author_pseudonym === '') {
        // if none given, use nickname
        $author_pseudonym = get_the_author_meta('nickname', $post->post_author);
    }
    ?>
    <div id="mn-authorwrap">
        <label for="mn-author"><?php _e('Pseudonym', 'textdomain'); ?>:</label>
        <input type="text" name="mn_author" id="mn-author" value="<?php echo esc_attr($author_pseudonym); ?>" <?php echo ($can_edit_post ? '' : 'disabled '); ?> />
    </div>
    <?php
    /**
     * Fires at the beginning of the publishing actions section of the Publish meta box.
     *
     * @since 2.7.0
     * @since 4.9.0 Added the `$post` parameter.
     *
     * @param WP_Post|null $post WP_Post object for the current post on Edit Post screen,
     *                           null on Edit Link screen.
     */
    do_action( 'post_submitbox_start', $post );
    ?>
    <div id="delete-action">
        <?php
        // if ( current_user_can( 'delete_post', $post_id ) ) {
        if ( $can_publish && ($post->post_status === 'pending' || !$user_is_author) ) {
            // Show rejection button and dialog
            preg_match('/^REASON FOR REJECTION:\n([\s\S]*?)\n---\n\n/', $post->post_content, $reject_rationale_matches);  // extract rejection rationale
            $reject_rationale = $reject_rationale_matches[1];
            ?>
            <button type="button" class="button submitdelete" id="mn-show-reject-dialog"><?php _e( 'Reject', 'textdomain' ); ?></button>
            <?php self::notification_dialog('mn-reject-dialog', __('Reject Article', 'textdomain'),
                array(self::class, 'render_rejection_dialog'), true, $reject_rationale); ?>
            <?php
        } elseif ( current_user_can( 'delete_post', $post_id ) && $can_edit_post ) {
            if ( ! EMPTY_TRASH_DAYS ) {
                $delete_text = __( 'Delete permanently' );
            } else {
                $delete_text = __( 'Delete draft' );
            }
            ?>
            <a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post_id ); ?>"><?php echo $delete_text; ?></a>
            <?php
        }
        ?>
    </div>

    <div id="publishing-action">
        <?php
        $cur_issue = get_option(Consts\CURRENT_ISSUE_OPTION_NAME, Consts\CURRENT_ISSUE_OPTION_DEFAULT);  // [volume_num, issue_num]
        $cur_issue_tag = "v{$cur_issue[0]}i{$cur_issue[1]}";
        $post_tags = get_the_tags($post_id);

        if ($post_tags === false) {
        	$post_tags = [];
        } else {
        	$cur_issue_tag = $post_tags[0]->name;  // just get the first tag in the list of tags
        }

        $cur_issue_tag = '<code>' . esc_html($cur_issue_tag) . '</code>';

        if (count($post_tags) >= 2) {
            $cur_issue_tag = $cur_issue_tag . ', &hellip;';
        }

        if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ), true ) || 0 === $post_id ) {
            if ( $can_publish && ($post->post_status === 'pending' || !$user_is_author) ) :
                /* if ( ! empty( $post->post_date_gmt ) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) :
                    ?>
                    <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr_x( 'Schedule', 'post action/button label' ); ?>" />
                    <?php submit_button( _x( 'Schedule', 'post action/button label' ), 'primary large', 'publish', false ); ?>
                    <?php
                else :
                    ?>
                    <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ); ?>" />
                    <?php submit_button( __( 'Publish' ), 'primary large', 'publish', false ); ?>
                    <?php
                endif; */
                ?>
                <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Mark Editor Okayed', 'textdomain' ); ?>" />
                <?php /*submit_button( __( 'Mark Editor Okayed', 'textdomain' ), 'primary large', 'mn-approve', false, [ 'id' => 'publish' ] );*/ ?>
                <button type="submit" name="mn-approve" id="publish" class="button button-primary button-large" value="<?php esc_attr_e( 'Mark Editor Okayed' ); ?>">
                    Mark Editor Okayed for
                    <span id="mn_publish-tag"><?php echo $cur_issue_tag; ?></span>
                </button>
                <div class="hidden">
                    <span id="mn_publish-default-tag"><?php echo esc_html("v{$cur_issue[0]}i{$cur_issue[1]}"); ?></span>
                </div>
                <?php
            // else :
            elseif ( $post->post_status !== 'pending' && $can_edit_post ) :
                ?>
                <span class="spinner"></span>
                <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Submit for Review' ); ?>" />
                <?php /*submit_button( __( 'Submit for Review' ), 'primary large', 'publish', false );*/ ?>
                <button type="submit" name="pending" id="publish" class="button button-primary button-large" value="<?php esc_attr_e( 'Submit for Review' ); ?>">
                    Submit to
                    <span id="mn_publish-tag"><?php echo $cur_issue_tag; ?></span>
                </button>
                <div class="hidden">
                    <span id="mn_publish-default-tag"><?php echo esc_html("v{$cur_issue[0]}i{$cur_issue[1]}"); ?></span>
                </div>
                <?php
            endif;
        } else {
            ?>
            <span class="spinner"></span>
            <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ); ?>" />
            <?php submit_button( __( 'Update' ), 'primary large', 'save', false, array( 'id' => 'publish' ) ); ?>
            <?php
        }
        ?>
    </div>
    <div class="clear"></div>
</div>

</div>
		<?php
	}
}
