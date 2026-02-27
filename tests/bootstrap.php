<?php

/**
 * PHPUnit bootstrap file for LGPD plugin.
 * Requires WP automated testing framework setup.
 */

// load WordPress test environment
if ( ! defined( 'WP_TESTS_DIR' ) ) {
    $dir = getenv( 'WP_TESTS_DIR' );
    if ( ! $dir ) {
        $dir = '/tmp/wordpress-tests-lib'; // default location
    }
    define( 'WP_TESTS_DIR', $dir );
}

require_once WP_TESTS_DIR . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname( __DIR__ ) . '/plugin-lgpd.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require_once WP_TESTS_DIR . '/includes/bootstrap.php';
