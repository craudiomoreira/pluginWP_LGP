<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// helper to format date to PT-BR
function lgpd_format_date_pt_br( $date_string ) {
    $datetime = new DateTime( $date_string );
    return $datetime->format( 'd/m/Y H:i:s' );
}

// simple user-agent parsers for OS and browser
function lgpd_detect_os( $ua ) {
    $ua = strtolower( $ua );
    if ( strpos( $ua, 'windows' ) !== false ) {
        return 'Windows';
    }
    if ( strpos( $ua, 'mac os x' ) !== false || strpos( $ua, 'macintosh' ) !== false ) {
        return 'MacOS';
    }
    if ( strpos( $ua, 'linux' ) !== false ) {
        return 'Linux';
    }
    if ( strpos( $ua, 'android' ) !== false ) {
        return 'Android';
    }
    if ( strpos( $ua, 'iphone' ) !== false || strpos( $ua, 'ipad' ) !== false ) {
        return 'iOS';
    }
    return 'Other';
}

function lgpd_detect_browser( $ua ) {
    $ua = strtolower( $ua );
    if ( strpos( $ua, 'chrome' ) !== false && strpos( $ua, 'edg/' ) === false && strpos( $ua, 'opr/' ) === false ) {
        return 'Chrome';
    }
    if ( strpos( $ua, 'firefox' ) !== false ) {
        return 'Firefox';
    }
    if ( strpos( $ua, 'safari' ) !== false && strpos( $ua, 'chrome' ) === false ) {
        return 'Safari';
    }
    if ( strpos( $ua, 'edg/' ) !== false || strpos( $ua, 'edge' ) !== false ) {
        return 'Edge';
    }
    if ( strpos( $ua, 'opr/' ) !== false || strpos( $ua, 'opera' ) !== false ) {
        return 'Opera';
    }
    if ( strpos( $ua, 'msie' ) !== false || strpos( $ua, 'trident' ) !== false ) {
        return 'Internet Explorer';
    }
    return 'Other';
}

function lgpd_register_logs_page() {
    add_submenu_page(
        'options-general.php',
        __( 'LGPD Logs', 'lgpd-consent' ),
        __( 'LGPD Logs', 'lgpd-consent' ),
        'manage_options',
        'lgpd-logs',
        'lgpd_logs_page_html'
    );
}
add_action( 'admin_menu', 'lgpd_register_logs_page' );
add_action( 'admin_enqueue_scripts', 'lgpd_enqueue_admin_assets' );

function lgpd_enqueue_admin_assets( $hook ) {
    // only load on our logs page
    if ( 'settings_page_lgpd-logs' !== $hook ) {
        return;
    }
    wp_enqueue_script( 'lgpd-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null );
}

function lgpd_logs_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'lgpd_consents';

    // ensure table exists before querying
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
    if ( $exists !== $table ) {
        // try to create table automatically
        if ( function_exists( 'lgpd_activate' ) ) {
            lgpd_activate();
            echo '<div class="notice notice-success"><p>' . esc_html__( 'LGPD logs table was missing and has been created automatically.', 'lgpd-consent' ) . '</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'LGPD logs table not found. Please reactivate the plugin to create it.', 'lgpd-consent' ) . '</p></div>';
        }
        // recheck existence; if still absent bail out
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( $exists !== $table ) {
            return;
        }
    }

    // handle delete/clear actions
    if ( isset( $_POST['lgpd_logs_action'] ) ) {
        check_admin_referer( 'lgpd_logs_action', 'lgpd_logs_nonce' );
        $action = sanitize_text_field( wp_unslash( $_POST['lgpd_logs_action'] ) );
        if ( 'delete_selected' === $action && ! empty( $_POST['lgpd_ids'] ) && is_array( $_POST['lgpd_ids'] ) ) {
            $ids = array_map( 'intval', wp_unslash( $_POST['lgpd_ids'] ) );
            $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
            $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id IN ($placeholders)", $ids ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Selected log entries deleted.', 'lgpd-consent' ) . '</p></div>';
        } elseif ( 'clear_all' === $action ) {
            $wpdb->query( "TRUNCATE TABLE $table" );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'All log entries have been cleared.', 'lgpd-consent' ) . '</p></div>';
        }
    }

    // handle export request
    if ( isset( $_GET['lgpd_export'] ) ) {
        $start = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
        $end   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
        $where = array();
        if ( $start ) {
            $where[] = $wpdb->prepare( "consented_at >= %s", $start . ' 00:00:00' );
        }
        if ( $end ) {
            $where[] = $wpdb->prepare( "consented_at <= %s", $end . ' 23:59:59' );
        }
        $sql = "SELECT * FROM $table";
        if ( $where ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }
        $results = $wpdb->get_results( $sql );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=lgpd-logs.csv' );
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'ID', 'Date/Time', 'IP', 'Location', 'User Agent' ) );
        foreach ( $results as $row ) {
            fputcsv( $output, array( $row->id, $row->consented_at, $row->ip, $row->location, $row->user_agent ) );
        }
        exit;
    }

    // filters
    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
    $where_clauses = array();
    if ( $start_date ) {
        $where_clauses[] = $wpdb->prepare( "consented_at >= %s", $start_date . ' 00:00:00' );
    }
    if ( $end_date ) {
        $where_clauses[] = $wpdb->prepare( "consented_at <= %s", $end_date . ' 23:59:59' );
    }
    $sql = "SELECT * FROM $table";
    if ( $where_clauses ) {
        $sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
    }
    $sql .= " ORDER BY consented_at DESC";
    // fetch limited entries for table
    $entries = $wpdb->get_results( $sql . " LIMIT 100" );
    // fetch all matching entries for statistics
    $stats_entries = $wpdb->get_results( $sql );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'LGPD Consent Logs', 'lgpd-consent' ); ?></h1>
        <?php
        // compute statistics
        $os_counts = array();
        $country_counts = array();
        $browser_counts = array();
        foreach ( $stats_entries as $e ) {
            $os = lgpd_detect_os( $e->user_agent );
            $os_counts[ $os ] = ( isset( $os_counts[ $os ] ) ? $os_counts[ $os ] : 0 ) + 1;

            $parts = explode( ' / ', $e->location );
            $country = $parts[0] ?: 'Unknown';
            $country_counts[ $country ] = ( isset( $country_counts[ $country ] ) ? $country_counts[ $country ] : 0 ) + 1;

            $browser = lgpd_detect_browser( $e->user_agent );
            $browser_counts[ $browser ] = ( isset( $browser_counts[ $browser ] ) ? $browser_counts[ $browser ] : 0 ) + 1;
        }
        ?>
        <div class="lgpd-summary-charts" style="margin-bottom:1.5em;max-width:100%;margin-left:auto;margin-right:auto;overflow-x:auto;">
            <h2><?php esc_html_e( 'Resumo dos registros', 'lgpd-consent' ); ?></h2>
            <style>
            .lgpd-summary-charts { display:flex; flex-wrap:nowrap; }
            .lgpd-summary-charts canvas {flex:0 0 auto; width:220px !important; height:180px !important; margin-right:12px;}
            .lgpd-summary-charts canvas:last-child {margin-right:0;}
            </style>
            <canvas id="lgpd-os-chart"></canvas>
            <canvas id="lgpd-country-chart"></canvas>
            <canvas id="lgpd-browser-chart"></canvas>
        </div>
        <script>
            var lgpdOSData = <?php echo wp_json_encode( $os_counts ); ?>;
            var lgpdCountryData = <?php echo wp_json_encode( $country_counts ); ?>;
            var lgpdBrowserData = <?php echo wp_json_encode( $browser_counts ); ?>;
        </script>
        <script>
        jQuery(function($){
            function renderChart(ctxId, dataObj, title){
                var ctx = document.getElementById(ctxId).getContext('2d');
                new Chart(ctx,{
                    type: 'pie',
                    data: {
                        labels: Object.keys(dataObj),
                        datasets: [{ data: Object.values(dataObj) }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        title: { display: true, text: title }
                    }
                });
            }
            renderChart('lgpd-os-chart', lgpdOSData, '<?php echo esc_js( __( 'Operating Systems', 'lgpd-consent' ) ); ?>');
            renderChart('lgpd-country-chart', lgpdCountryData, '<?php echo esc_js( __( 'Countries', 'lgpd-consent' ) ); ?>');
            renderChart('lgpd-browser-chart', lgpdBrowserData, '<?php echo esc_js( __( 'Browsers', 'lgpd-consent' ) ); ?>');
        });
        </script>
        <form method="get" style="margin-bottom:1em;">
            <input type="hidden" name="page" value="lgpd-logs" />
            <label><?php esc_html_e( 'Start date:', 'lgpd-consent' ); ?> <input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" /></label>
            <label><?php esc_html_e( 'End date:', 'lgpd-consent' ); ?> <input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" /></label>
            <button class="button" type="submit"><?php esc_html_e( 'Filter', 'lgpd-consent' ); ?></button>
            <button class="button" type="submit" name="lgpd_export" value="1"><?php esc_html_e( 'Export CSV', 'lgpd-consent' ); ?></button>
        </form>
        <form method="post" id="lgpd-log-actions-form">
            <?php wp_nonce_field( 'lgpd_logs_action', 'lgpd_logs_nonce' ); ?>
            <button class="button button-secondary" type="submit" name="lgpd_logs_action" value="delete_selected"><?php esc_html_e( 'Delete Selected', 'lgpd-consent' ); ?></button>
            <button class="button button-secondary" type="submit" name="lgpd_logs_action" value="clear_all" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove all log entries?', 'lgpd-consent' ) ); ?>');"><?php esc_html_e( 'Clear All', 'lgpd-consent' ); ?></button>
        </form>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th><input type="checkbox" id="lgpd-select-all" /></th>
                    <th>ID</th>
                    <th><?php esc_html_e( 'Date/Time', 'lgpd-consent' ); ?></th>
                    <th><?php esc_html_e( 'IP', 'lgpd-consent' ); ?></th>
                    <th><?php esc_html_e( 'Location', 'lgpd-consent' ); ?></th>
                    <th><?php esc_html_e( 'User Agent', 'lgpd-consent' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $entries ) : ?>
                    <?php foreach ( $entries as $e ) : ?>
                        <tr>
                            <td><input type="checkbox" name="lgpd_ids[]" value="<?php echo esc_attr( $e->id ); ?>" /></td>
                            <td><?php echo esc_html( $e->id ); ?></td>
                            <td><?php echo esc_html( lgpd_format_date_pt_br( $e->consented_at ) ); ?></td>
                            <td><?php echo esc_html( $e->ip ); ?></td>
                            <td><?php echo esc_html( $e->location ); ?></td>
                            <td><?php echo esc_html( $e->user_agent ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6"><?php esc_html_e( 'No entries found.', 'lgpd-consent' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <script>
        jQuery(function($){
            $('#lgpd-select-all').on('change', function(){
                $('#lgpd-log-actions-form').find('input[name="lgpd_ids[]"]').prop('checked', $(this).prop('checked'));
            });
        });
        </script>
    </div>
    <?php
}
