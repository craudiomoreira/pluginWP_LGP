<?php
/**
 * Plugin Name: LGPD Consent Modal
 * Plugin URI:  https://example.com/lgpd-consent
 * Description: Exibe um modal de consentimento compatível com Elementor e coleta dados de consentimento.
 * Version:     1.0.0
 * Author:      Seu Nome
 * Author URI:  https://example.com
 * Text Domain: lgpd-consent
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// plugin constants
if ( ! defined( 'LGPD_PLUGIN_DIR' ) ) {
    define( 'LGPD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'LGPD_PLUGIN_URL' ) ) {
    define( 'LGPD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// include required files
require_once LGPD_PLUGIN_DIR . 'includes/admin-settings.php';
require_once LGPD_PLUGIN_DIR . 'includes/admin-logs.php';
require_once LGPD_PLUGIN_DIR . 'includes/public-frontend.php';


// activation hook: create database table
function lgpd_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lgpd_consents';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        consented_at datetime NOT NULL,
        ip varchar(100) NOT NULL,
        location varchar(255) DEFAULT '' NOT NULL,
        user_agent text NOT NULL,
        additional_info text DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // default settings
    $defaults = array(
        'enabled'              => 1,
        'modal_title'          => __( 'Política de Privacidade', 'lgpd-consent' ),
        'consent_text'         => __( 'We use cookies to improve your experience.', 'lgpd-consent' ),
        'consent_link'         => '',
        'modal_position'       => 'center',
        'modal_h_position'     => 'center',
        'font_family'          => 'Arial',
        'font_family_custom'   => '',
        'font_size'            => '16px',
        'background_color'     => '#ffffff',
        'font_color'           => '#000000',
        'button_accent_color'  => '#0073aa',
        'button_accent_url'    => '',
        'button_secondary_color' => '#767676',
        'button_secondary_url' => '',
        'button_font_size'     => '12px',
        'button_font_family'   => 'Arial',
        'button_font_family_custom' => '',
        'cookie_duration'      => 365,
    );
    add_option( 'lgpd_settings', $defaults );
}
register_activation_hook( __FILE__, 'lgpd_activate' );

// load textdomain for translations
function lgpd_load_textdomain() {
    load_plugin_textdomain( 'lgpd-consent', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'lgpd_load_textdomain' );
