<?php

// if uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// clear database
global $wpdb;
$table = $wpdb->prefix . 'lgpd_consents';
$wpdb->query( "DROP TABLE IF EXISTS $table" );

// delete options
delete_option( 'lgpd_settings' );
