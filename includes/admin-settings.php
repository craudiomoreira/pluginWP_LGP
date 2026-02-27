<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// add settings page
function lgpd_register_settings_page() {
    add_options_page(
        __( 'LGPD Consent', 'lgpd-consent' ),
        __( 'LGPD Consent', 'lgpd-consent' ),
        'manage_options',
        'lgpd-consent',
        'lgpd_settings_page_html'
    );
}
add_action( 'admin_menu', 'lgpd_register_settings_page' );

// register settings
function lgpd_register_settings() {
    register_setting( 'lgpd_settings_group', 'lgpd_settings' );

    add_settings_section(
        'lgpd_main_section',
        '',
        '__return_false',
        'lgpd-consent'
    );

    $fields = array(
        'enabled'                => __( 'Ativar modal', 'lgpd-consent' ),
        'modal_title'            => __( 'Título do modal', 'lgpd-consent' ),
        'consent_text'           => __( 'Texto do consentimento', 'lgpd-consent' ),
        'consent_link'           => __( 'URL das regras', 'lgpd-consent' ),
        'modal_position'         => __( 'Posição vertical', 'lgpd-consent' ),
        'modal_h_position'       => __( 'Posição horizontal', 'lgpd-consent' ),
        'font_family'            => __( 'Fonte do texto', 'lgpd-consent' ),
        'font_family_custom'     => __( 'Fonte customizada (Google Fonts)', 'lgpd-consent' ),
        'font_size'              => __( 'Tamanho da fonte', 'lgpd-consent' ),
        'background_color'       => __( 'Cor de fundo', 'lgpd-consent' ),
        'font_color'             => __( 'Cor da fonte', 'lgpd-consent' ),
        'button_accent_color'    => __( 'Cor botão principal', 'lgpd-consent' ),
        'button_accent_url'      => __( 'URL botão principal', 'lgpd-consent' ),
        'button_secondary_color' => __( 'Cor botão secundário', 'lgpd-consent' ),
        'button_secondary_url'   => __( 'URL botão secundário', 'lgpd-consent' ),
        'button_font_size'       => __( 'Tamanho fonte botões', 'lgpd-consent' ),
        'button_font_family'     => __( 'Fonte dos botões', 'lgpd-consent' ),
        'button_font_family_custom' => __( 'Fonte botões customizada (Google Fonts)', 'lgpd-consent' ),
        'cookie_duration'        => __( 'Duração do cookie (dias)', 'lgpd-consent' ),
    );

    foreach ( $fields as $name => $label ) {
        add_settings_field(
            'lgpd_' . $name,
            $label,
            'lgpd_settings_field_callback',
            'lgpd-consent',
            'lgpd_main_section',
            array( 'name' => $name )
        );
    }
}
add_action( 'admin_init', 'lgpd_register_settings' );

function lgpd_settings_field_callback( $args ) {
    $options = get_option( 'lgpd_settings', array() );
    $name    = $args['name'];
    $value   = isset( $options[ $name ] ) ? esc_attr( $options[ $name ] ) : '';
    switch ( $name ) {
        case 'enabled':
            echo '<input type="checkbox" name="lgpd_settings[' . $name . ']" value="1" ' . checked( $value, 1, false ) . ' />';
            break;
        case 'modal_position':
            echo '<select name="lgpd_settings[' . $name . ']"><option value="top"' . selected( $value, 'top', false ) . '>Top</option><option value="center"' . selected( $value, 'center', false ) . '>Center</option><option value="bottom"' . selected( $value, 'bottom', false ) . '>Bottom</option></select>';
            break;
        case 'modal_h_position':
            echo '<select name="lgpd_settings[' . $name . ']"><option value="left"' . selected( $value, 'left', false ) . '>Left</option><option value="center"' . selected( $value, 'center', false ) . '>Center</option><option value="right"' . selected( $value, 'right', false ) . '>Right</option></select>';
            break;
        case 'font_family':
            $fonts = array( 'Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Georgia', 'Verdana', 'Trebuchet MS' );
            echo '<select name="lgpd_settings[' . $name . ']"><option value="">-- Custom (use custom field below) --</option>';
            foreach ( $fonts as $font ) {
                echo '<option value="' . esc_attr( $font ) . '"' . selected( $value, $font, false ) . '>' . esc_html( $font ) . '</option>';
            }
            echo '</select>';
            break;
        case 'button_font_family':
            $fonts = array( 'Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Georgia', 'Verdana', 'Trebuchet MS' );
            echo '<select name="lgpd_settings[' . $name . ']"><option value="">-- Custom (use custom field below) --</option>';
            foreach ( $fonts as $font ) {
                echo '<option value="' . esc_attr( $font ) . '"' . selected( $value, $font, false ) . '>' . esc_html( $font ) . '</option>';
            }
            echo '</select>';
            break;
        case 'background_color':
        case 'font_color':
        case 'button_accent_color':
        case 'button_secondary_color':
            echo '<input type="text" class="lgpd-color-field" name="lgpd_settings[' . $name . ']" value="' . $value . '" />';
            break;
        case 'cookie_duration':
            echo '<input type="number" min="1" name="lgpd_settings[' . $name . ']" value="' . $value . '" class="small-text" />';
            break;
        default:
            echo '<input type="text" name="lgpd_settings[' . $name . ']" value="' . $value . '" class="regular-text" />';
    }
}

function lgpd_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'LGPD Consent Settings', 'lgpd-consent' ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'lgpd_settings_group' );
            do_settings_sections( 'lgpd-consent' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// enqueue admin scripts for color picker
function lgpd_admin_scripts( $hook ) {
    if ( 'settings_page_lgpd-consent' !== $hook ) {
        return;
    }
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'lgpd-admin-js', LGPD_PLUGIN_URL . 'assets/js/admin.js', array( 'wp-color-picker', 'jquery' ), '1.0', true );
}
add_action( 'admin_enqueue_scripts', 'lgpd_admin_scripts' );
