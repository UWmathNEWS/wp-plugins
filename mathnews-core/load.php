<?php

define('MATHNEWS_CORE_BASEDIR', plugin_dir_path(__FILE__));

/**
 * Load consts
 *
 * @since 1.0.0
 */
function load_consts() {
	require_once plugin_dir_path(__FILE__) . 'includes/mathnews-core-consts.php';
}

/**
 * Load utils
 *
 * @since 1.0.0
 */
function load_utils() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-mathnews-core-utils.php';
}
