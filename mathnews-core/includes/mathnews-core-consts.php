<?php

/**
 * Constants for the plugin
 *
 * @link       All licensing queries should be directed to mathnews@gmail.com
 * @since      1.0.0
 *
 * @package    Mathnews_Core
 * @subpackage Mathnews_Core/includes
 */

namespace Mathnews\WP\Core\Consts;

/**
 * Article post type for publishing
 *
 * @since 1.0.0
 */
const POST_TYPE = 'post';

/**
 * Issue post type for publishing
 *
 * @since 1.0.0
 */
const ISSUE_TYPE = 'mn_issue';

/**
 * Approved category name
 *
 * @since 1.0.0
 */
const APPROVED_CAT_NAME = 'Editor okayed';

/**
 * Rejected category name
 *
 * @since 1.0.0
 */
const REJECTED_CAT_NAME = 'Rejected';

/**
 * Backissues category name
 *
 * @since 1.0.0
 */
const BACKISSUE_CAT_NAME = 'backissues';

/**
 * Subtitle meta key name
 *
 * @since 1.0.0
 */
const SUBTITLE_META_KEY_NAME = 'mn_subtitle';

/**
 * Author meta key name
 *
 * @since 1.0.0
 */
const AUTHOR_META_KEY_NAME = 'mn_author';

/**
 * Postscript meta key name
 *
 * @since 1.0.0
 */
const POSTSCRIPT_META_KEY_NAME = 'mn_postscript';

/**
 * Current issue settings page slug
 *
 * @since 1.0.0
 */
const CURRENT_ISSUE_SETTINGS_SLUG = 'set-current-issue';

/**
 * Current issue option name
 *
 * @since 1.0.0
 */
const CURRENT_ISSUE_OPTION_NAME = 'mn_current_issue';

/**
 * Current issue option default
 *
 * @since 1.0.0
 * @since 1.4.0 Change to a tag that will never happen in real life
 */
const CURRENT_ISSUE_OPTION_DEFAULT = ['1XX', 'Y'];  // [volume_num, issue_num]

/**
 * Plugin settings page slug
 *
 * @since 1.2.0
 */
const CORE_SETTINGS_SLUG = 'mn-settings';

/**
 * Helpful links list option name
 *
 * @since 1.2.0
 */
const HELPFUL_LINKS_OPTION_NAME = 'mn_helpful_links';

/**
 * Onboarding option key name
 *
 * @since 1.0.0
 * @deprecated
 */
const ONBOARDING_OPTION_KEY_NAME = 'mn_onboarded_successfully';
