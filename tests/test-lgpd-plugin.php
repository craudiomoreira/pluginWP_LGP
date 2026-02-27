<?php

/**
 * PHPUnit tests for LGPD plugin.
 */

class LGPD_Plugin_Test extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        // ensure plugin is activated
        include_once WP_PLUGIN_DIR . '/lgpd-consent/plugin-lgpd.php';
    }

    public function test_table_exists_after_activation() {
        global $wpdb;
        $table = $wpdb->prefix . 'lgpd_consents';
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table );
    }

    public function test_default_settings_present() {
        $opts = get_option( 'lgpd_settings' );
        $this->assertIsArray( $opts );
        $this->assertArrayHasKey( 'consent_text', $opts );
        $this->assertArrayHasKey( 'cookie_duration', $opts );
        $this->assertArrayHasKey( 'enabled', $opts );
        $this->assertEquals( 1, $opts['enabled'] );
    }
}
