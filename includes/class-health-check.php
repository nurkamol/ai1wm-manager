<?php
/**
 * System health / diagnostics checks for the AI1WM Manager environment.
 *
 * Each check returns an associative array:
 *   id      => machine key
 *   label   => human-readable title
 *   status  => 'ok' | 'warning' | 'error'
 *   value   => short current value (e.g. "7.4.3")
 *   message => explanatory text
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Health_Check {

    const MIN_PHP = '7.4';
    const MIN_WP  = '5.6';

    /** @var AI1WM_Manager_Extensions_Manager */
    private $ext;

    public function __construct() {
        $this->ext = new AI1WM_Manager_Extensions_Manager();
    }

    /**
     * Run all checks.
     *
     * @return array List of check result arrays.
     */
    public function run() {
        return array(
            $this->check_ai1wm_installed(),
            $this->check_extensions_file_writable(),
            $this->check_php_version(),
            $this->check_wp_version(),
            $this->check_log_table(),
            $this->check_cron(),
            $this->check_last_backup(),
            $this->check_backups_storage(),
        );
    }

    /**
     * Summarise the checks into counts by status.
     *
     * @param array $checks
     * @return array { ok:int, warning:int, error:int, total:int }
     */
    public static function summarize( $checks ) {
        $summary = array( 'ok' => 0, 'warning' => 0, 'error' => 0, 'total' => count( $checks ) );
        foreach ( $checks as $check ) {
            $status = $check['status'] ?? 'ok';
            if ( isset( $summary[ $status ] ) ) {
                $summary[ $status ]++;
            }
        }
        return $summary;
    }

    // ── Individual checks ─────────────────────────────────────────────────────

    private function check_ai1wm_installed() {
        $exists = $this->ext->extensions_file_exists();
        return array(
            'id'      => 'ai1wm_installed',
            'label'   => __( 'All-in-One WP Migration', 'ai1wm-manager' ),
            'status'  => $exists ? 'ok' : 'error',
            'value'   => $exists ? __( 'Detected', 'ai1wm-manager' ) : __( 'Not found', 'ai1wm-manager' ),
            'message' => $exists
                ? __( 'The parent plugin and its extensions file were found.', 'ai1wm-manager' )
                : sprintf( __( 'Extensions file not found at %s. Most features are unavailable until the plugin is installed.', 'ai1wm-manager' ), $this->ext->get_file_path() ),
        );
    }

    private function check_extensions_file_writable() {
        $path     = $this->ext->get_file_path();
        $exists   = $this->ext->extensions_file_exists();
        $writable = $exists && is_writable( $path );

        if ( ! $exists ) {
            $status  = 'warning';
            $value   = __( 'N/A', 'ai1wm-manager' );
            $message = __( 'Cannot check — the extensions file does not exist yet.', 'ai1wm-manager' );
        } elseif ( $writable ) {
            $status  = 'ok';
            $value   = __( 'Writable', 'ai1wm-manager' );
            $message = __( 'Extension version updates can be written to disk.', 'ai1wm-manager' );
        } else {
            $status  = 'error';
            $value   = __( 'Read-only', 'ai1wm-manager' );
            $message = __( 'The extensions file is not writable. Version updates and reverts will fail until permissions are fixed.', 'ai1wm-manager' );
        }

        return array(
            'id'      => 'extensions_writable',
            'label'   => __( 'Extensions File Writable', 'ai1wm-manager' ),
            'status'  => $status,
            'value'   => $value,
            'message' => $message,
        );
    }

    private function check_php_version() {
        $ok = version_compare( PHP_VERSION, self::MIN_PHP, '>=' );
        return array(
            'id'      => 'php_version',
            'label'   => __( 'PHP Version', 'ai1wm-manager' ),
            'status'  => $ok ? 'ok' : 'error',
            'value'   => PHP_VERSION,
            'message' => $ok
                ? __( 'PHP meets the minimum requirement.', 'ai1wm-manager' )
                : sprintf( __( 'PHP %1$s or higher is required (running %2$s).', 'ai1wm-manager' ), self::MIN_PHP, PHP_VERSION ),
        );
    }

    private function check_wp_version() {
        global $wp_version;
        $ok = version_compare( $wp_version, self::MIN_WP, '>=' );
        return array(
            'id'      => 'wp_version',
            'label'   => __( 'WordPress Version', 'ai1wm-manager' ),
            'status'  => $ok ? 'ok' : 'warning',
            'value'   => $wp_version,
            'message' => $ok
                ? __( 'WordPress meets the minimum requirement.', 'ai1wm-manager' )
                : sprintf( __( 'WordPress %1$s or higher is recommended (running %2$s).', 'ai1wm-manager' ), self::MIN_WP, $wp_version ),
        );
    }

    private function check_log_table() {
        global $wpdb;
        $table  = $wpdb->prefix . 'ai1wm_manager_logs';
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

        return array(
            'id'      => 'log_table',
            'label'   => __( 'Activity Log Table', 'ai1wm-manager' ),
            'status'  => $exists ? 'ok' : 'error',
            'value'   => $exists ? __( 'Present', 'ai1wm-manager' ) : __( 'Missing', 'ai1wm-manager' ),
            'message' => $exists
                ? __( 'The activity log database table exists.', 'ai1wm-manager' )
                : __( 'The activity log table is missing. Try deactivating and reactivating the plugin to recreate it.', 'ai1wm-manager' ),
        );
    }

    private function check_cron() {
        $options   = get_option( 'ai1wm_manager_options', array() );
        $schedule  = $options['auto_backup_schedule'] ?? 'disabled';
        $timestamp = wp_next_scheduled( AI1WM_Manager_Scheduler::HOOK );

        if ( $schedule === 'disabled' ) {
            $status  = 'ok';
            $value   = __( 'Disabled', 'ai1wm-manager' );
            $message = __( 'Scheduled auto-backups are turned off. Enable them in Plugin Options if you want automatic backups.', 'ai1wm-manager' );
        } elseif ( $timestamp ) {
            $status  = 'ok';
            $value   = ucfirst( $schedule );
            $message = sprintf( __( 'Next run: %s.', 'ai1wm-manager' ), date_i18n( 'M j, Y H:i', $timestamp ) );
        } else {
            $status  = 'warning';
            $value   = __( 'Not scheduled', 'ai1wm-manager' );
            $message = __( 'Auto-backup is enabled but no cron event is scheduled. Re-save Plugin Options to reschedule it.', 'ai1wm-manager' );
        }

        // Warn if WP-Cron is disabled entirely.
        if ( $schedule !== 'disabled' && defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
            $status  = 'warning';
            $message .= ' ' . __( 'Note: DISABLE_WP_CRON is set, so a real system cron must trigger wp-cron.php.', 'ai1wm-manager' );
        }

        return array(
            'id'      => 'cron',
            'label'   => __( 'Scheduled Backups (WP-Cron)', 'ai1wm-manager' ),
            'status'  => $status,
            'value'   => $value,
            'message' => $message,
        );
    }

    /**
     * All backups regardless of type. get_all('all') only matches extension
     * backups, so merge both prefixes explicitly for an accurate total.
     *
     * @return array
     */
    private function all_backups() {
        return array_merge(
            AI1WM_Manager_Backup_Manager::get_all( 'extension' ),
            AI1WM_Manager_Backup_Manager::get_all( 'settings' )
        );
    }

    private function check_last_backup() {
        $backups = $this->all_backups();

        if ( empty( $backups ) ) {
            return array(
                'id'      => 'last_backup',
                'label'   => __( 'Most Recent Backup', 'ai1wm-manager' ),
                'status'  => 'warning',
                'value'   => __( 'None', 'ai1wm-manager' ),
                'message' => __( 'No backups exist yet. Create one from the Extensions or Settings tab.', 'ai1wm-manager' ),
            );
        }

        // get_all() sorts newest-first by option_name; find the max timestamp to be safe.
        $latest = 0;
        foreach ( $backups as $backup ) {
            if ( ! empty( $backup['timestamp'] ) && $backup['timestamp'] > $latest ) {
                $latest = (int) $backup['timestamp'];
            }
        }

        $age_days = ( time() - $latest ) / DAY_IN_SECONDS;
        $status   = $age_days > 30 ? 'warning' : 'ok';

        return array(
            'id'      => 'last_backup',
            'label'   => __( 'Most Recent Backup', 'ai1wm-manager' ),
            'status'  => $status,
            'value'   => human_time_diff( $latest, time() ) . ' ' . __( 'ago', 'ai1wm-manager' ),
            'message' => $status === 'ok'
                ? sprintf( __( 'Last backup created %s.', 'ai1wm-manager' ), date_i18n( 'M j, Y H:i', $latest ) )
                : sprintf( __( 'Your most recent backup is over 30 days old (%s). Consider creating a fresh one.', 'ai1wm-manager' ), date_i18n( 'M j, Y', $latest ) ),
        );
    }

    private function check_backups_storage() {
        $all   = $this->all_backups();
        $count = count( $all );

        return array(
            'id'      => 'backups_storage',
            'label'   => __( 'Stored Backups', 'ai1wm-manager' ),
            'status'  => 'ok',
            'value'   => sprintf( _n( '%d backup', '%d backups', $count, 'ai1wm-manager' ), $count ),
            'message' => __( 'Backups are stored in the WordPress options table. Download important backups to keep an off-site copy.', 'ai1wm-manager' ),
        );
    }
}
