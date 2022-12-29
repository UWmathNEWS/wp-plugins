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
use Mathnews\WP\Core\Display;
use Mathnews\WP\Core\Utils;

Utils::require_core('class-mathnews-core-display.php');

class PostUI {
	/**
	 * Render a textarea when rejecting an article
	 *
	 * @since 1.0.0
	 */
	static public function render_rejection_dialog($reject_rationale) {
		?>
<div>
    <p>Please state why this article is being rejected.</p>
    <textarea id="mn-reject-rationale" name="mn-reject-rationale" class="widefat" rows="10"><?php echo esc_textarea($reject_rationale); ?></textarea>
    <p>
        <input id="mn-reject-draft" name="mn-reject-draft" type="checkbox">
        <label for="mn-reject-draft"><?php _e('Allow author to edit and resubmit (will untag article)', 'textdomain'); ?></label>
    </p>
    <p style="margin-bottom: 0;">
        <input id="mn-reject-email" name="mn-reject-email" type="checkbox" checked>
        <label for="mn-reject-email"><?php _e('Notify author of rejection', 'textdomain'); ?></label>
    </p>
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
    <input type="text" name="mn_subtitle" size="30" value="<?php echo esc_textarea( $subtitle ); ?>" id="mn-subtitle" spellcheck="true" autocomplete="off"
        placeholder="<?php _e('Subtitle (optional)', 'textdomain') ?>" <?php echo ($can_edit_post ? '' : 'disabled'); ?> />
</div>
		<?php
	}

    /**
     * Renders a helpful links meta box
     *
     * @since 1.2.0
     */
    static public function render_helpful_links_meta_box() {
        $links = preg_split('/\r\n|\r|\n/', get_option(Consts\HELPFUL_LINKS_OPTION_NAME), -1, PREG_SPLIT_NO_EMPTY);
        ?>
<ul>
    <?php
        foreach ($links as $link) {
            $url_and_title = explode(' ', $link, 2);
            ?>
    <li><a href="<?php echo esc_url($url_and_title[0]); ?>" target="_blank" rel="noreferer nofollow noopener"><?php echo esc_html($url_and_title[1]); ?></a></li>
            <?php
        }
    ?>
    <?php
        if (get_option('mn_helpful_links_show_onboarding', ['on'])[0] === 'on') {
            ?>
    <li><a href="#repeat-tour" onclick="window.mathNEWSShowOnboarding();return false">Repeat onboarding tour</a></li>
            <?php
        }
    ?>
</ul>
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
            <?php
            if (!$can_publish) {
                ?>
                <span id="post-status-display">
                    <?php
                    switch ( $post->post_status ) {
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
            } else {
                ?>
                    <input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ( 'auto-draft' === $post->post_status ) ? 'draft' : $post->post_status ); ?>" />
                    <label for="post_status" class="screen-reader-text"><?php _e( 'Set status' ); ?></label>
                    <select name="post_status" id="post_status"
                        onchange="jQuery('#save-post').val(`Save as ${this.options[this.selectedIndex].text.split(' ')[0]}`);return false;">
                            <option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e( 'Pending Review' ); ?></option>
                        <?php if ( 'auto-draft' === $post->post_status ) : ?>
                            <option<?php selected( $post->post_status, 'auto-draft' ); ?> value='draft'><?php _e( 'Draft' ); ?></option>
                        <?php else : ?>
                            <option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e( 'Draft' ); ?></option>
                        <?php endif; ?>
                    </select>
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
        <input type="text" name="mn_author" id="mn-author" value="<?php echo esc_textarea($author_pseudonym); ?>" <?php disabled(!$can_edit_post); ?> />
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
            preg_match('/^REASON FOR REJECTION:\r?\n([\s\S]*?)\r?\n---(\r?\n\r?\n)?/', $post->post_content, $reject_rationale_matches);  // extract rejection rationale
            $reject_rationale = $reject_rationale_matches[1];
            ?>
            <button type="button" class="button submitdelete" id="mn-show-reject-dialog"><?php _e( 'Reject', 'textdomain' ); ?></button>
            <?php Display::notification_dialog('mn-reject-dialog', __('Reject Article', 'textdomain'),
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
        $cur_issue_tag = Utils::get_current_tag();
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
                    <span id="mn_publish-default-tag"><?php echo esc_html(Utils::get_current_tag()); ?></span>
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
                    <span id="mn_publish-default-tag"><?php echo esc_html(Utils::get_current_tag()); ?></span>
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
