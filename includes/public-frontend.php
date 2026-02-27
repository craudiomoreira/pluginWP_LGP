<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get client IP address, considering proxies
 */
function lgpd_get_client_ip() {
    // Check for IP from share internet
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        // Handle multiple IPs in X-Forwarded-For (take first one)
        $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
        $ip = trim( $ips[0] );
    }
    // Check remote address
    elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = 'unknown';
    }
    
    // Validate IP
    if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
        return $ip;
    }
    return 'unknown';
}

// enqueue scripts/styles for front-end
function lgpd_enqueue_assets() {
    $options = get_option( 'lgpd_settings', array() );

    // load Google fonts if specified
    if ( ! empty( $options['font_family'] ) ) {
        $font = urlencode( $options['font_family'] );
        wp_enqueue_style( 'lgpd-google-font', "https://fonts.googleapis.com/css?family={$font}", array(), null );
    }
    if ( ! empty( $options['button_font_family'] ) ) {
        $btn_font = urlencode( $options['button_font_family'] );
        wp_enqueue_style( 'lgpd-google-font-btn', "https://fonts.googleapis.com/css?family={$btn_font}", array(), null );
    }

    wp_enqueue_style( 'lgpd-public-css', LGPD_PLUGIN_URL . 'assets/css/public.css' );
    wp_enqueue_script( 'lgpd-public-js', LGPD_PLUGIN_URL . 'assets/js/public.js', array( 'jquery' ), '1.0', true );
    $duration = isset( $options['cookie_duration'] ) ? intval( $options['cookie_duration'] ) : 365;

    wp_localize_script( 'lgpd-public-js', 'lgpd_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'lgpd_consent' ),
        'cookie_duration' => $duration,
        'privacy_link' => isset( $options['consent_link'] ) ? esc_url( $options['consent_link'] ) : '',
    ) );
}
add_action( 'wp_enqueue_scripts', 'lgpd_enqueue_assets' );

function lgpd_render_modal() {
    $options = get_option( 'lgpd_settings', array() );
    $title = isset( $options['modal_title'] ) ? $options['modal_title'] : __( 'Política de Privacidade', 'lgpd-consent' );
    $text = isset( $options['consent_text'] ) ? $options['consent_text'] : __( 'We use cookies to improve experience.', 'lgpd-consent' );
    $link = isset( $options['consent_link'] ) ? $options['consent_link'] : '#';
    $font = isset( $options['font_family'] ) ? $options['font_family'] : '';
    $size = isset( $options['font_size'] ) ? $options['font_size'] : '';
    $bg   = isset( $options['background_color'] ) ? $options['background_color'] : '#ffffff';
    $fc   = isset( $options['font_color'] ) ? $options['font_color'] : '#000000';
    $btn_accent = isset( $options['button_accent_color'] ) ? $options['button_accent_color'] : '#0073aa';
    $btn_secondary = isset( $options['button_secondary_color'] ) ? $options['button_secondary_color'] : '#767676';
    $btn_font_size = isset( $options['button_font_size'] ) ? $options['button_font_size'] : '12px';
    $btn_font_family = isset( $options['button_font_family'] ) ? $options['button_font_family'] : '';

    ob_start();
    ?>
    <style>
    #lgpd-accept {
        background: <?php echo esc_attr( $btn_accent ); ?> !important;
        color: #ffffff !important;
        font-size: <?php echo esc_attr( $btn_font_size ); ?> !important;
        <?php if ( $btn_font_family ) : ?>
        font-family: <?php echo esc_attr( $btn_font_family ); ?> !important;
        <?php endif; ?>
    }
    .lgpd-close {
        background: <?php echo esc_attr( $btn_secondary ); ?> !important;
        color: #ffffff !important;
        font-size: <?php echo esc_attr( $btn_font_size ); ?> !important;
        <?php if ( $btn_font_family ) : ?>
        font-family: <?php echo esc_attr( $btn_font_family ); ?> !important;
        <?php endif; ?>
    }
    </style>
    <div id="lgpd-consent-modal" class="lgpd-modal">
        <div class="lgpd-modal-content" style="background:<?php echo esc_attr( $bg ); ?>;color:<?php echo esc_attr( $fc ); ?>;font-family:<?php echo esc_attr( $font ); ?>;font-size:<?php echo esc_attr( $size ); ?>;">
            <?php if ( ! empty( $title ) ) : ?>
                <div class="lgpd-modal-header">
                    <h2 class="lgpd-modal-title"><?php echo esc_html( $title ); ?></h2>
                </div>
            <?php endif; ?>
            <div class="lgpd-modal-body">
                <p><?php echo wp_kses_post( $text ); ?> <a href="<?php echo esc_url( $link ); ?>" target="_blank"><?php esc_html_e( 'Saiba mais', 'lgpd-consent' ); ?></a></p>
                <div class="lgpd-buttons-wrapper">
                    <button id="lgpd-close" class="lgpd-button lgpd-close"><?php esc_html_e( 'Política de Privacidade', 'lgpd-consent' ); ?></button>
                    <button id="lgpd-accept" class="lgpd-button"><?php esc_html_e( 'Estou Ciente', 'lgpd-consent' ); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
    echo ob_get_clean();
}

// output modal in footer if allowed
function lgpd_maybe_print_modal() {
    if ( ! is_front_page() && ! is_home() ) {
        return;
    }
    $options = get_option( 'lgpd_settings', array() );
    if ( empty( $options['enabled'] ) ) {
        return;
    }
    // only show when no consent cookie
    if ( strpos( $_SERVER['HTTP_COOKIE'] ?? '', 'lgpd_consent=' ) !== false ) {
        return;
    }
    lgpd_render_modal();
}
add_action( 'wp_footer', 'lgpd_maybe_print_modal' );

// ajax handler to save consent
function lgpd_save_consent() {
    check_ajax_referer( 'lgpd_consent', 'nonce' );

    global $wpdb;
    $table = $wpdb->prefix . 'lgpd_consents';

    $ip = lgpd_get_client_ip();
    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

    // attempt basic geolocation (country/region/city) using ip-api.com
    $location = '';
    if ( 'unknown' !== $ip ) {
        $resp = wp_remote_get( "http://ip-api.com/json/{$ip}" );
        if ( ! is_wp_error( $resp ) ) {
            $data = json_decode( wp_remote_retrieve_body( $resp ), true );
            if ( isset( $data['status'] ) && 'success' === $data['status'] ) {
                $location = sanitize_text_field( $data['country'] . ' / ' . $data['regionName'] . ' / ' . $data['city'] );
            }
        }
    }

    $result = $wpdb->insert( $table, array(
        'consented_at'   => current_time( 'mysql' ),
        'ip'             => $ip,
        'location'       => $location,
        'user_agent'     => $ua,
    ) );

    if ( false === $result ) {
        // log failure to debug log so site admin can inspect
        error_log( 'LGPD Consent insert failed: ' . $wpdb->last_error );
    }

    wp_send_json_success();
}
add_action( 'wp_ajax_lgpd_save_consent', 'lgpd_save_consent' );
add_action( 'wp_ajax_nopriv_lgpd_save_consent', 'lgpd_save_consent' );
