<?php
/**
 * Handles plugin activation, deactivation, and database setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Installer {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        self::create_tables();
        self::migrate_legacy_data();

        // Store the installed version
        update_option( 'ai1wm_manager_version', AI1WM_MANAGER_VERSION );

        // Initialize default plugin options if not set
        if ( ! get_option( 'ai1wm_manager_options' ) ) {
            update_option( 'ai1wm_manager_options', self::default_options() );
        }

        // Clear the duplicate-cleanup flag from v3.x so it doesn't interfere
        delete_option( 'ai1wm_manager_cleaned_duplicates' );
    }

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate() {
        // Remove all scheduled cron events
        $timestamp = wp_next_scheduled( 'ai1wm_manager_scheduled_backup' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ai1wm_manager_scheduled_backup' );
        }
    }

    /**
     * Create required database tables.
     */
    public static function create_tables() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'ai1wm_manager_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_login varchar(60) NOT NULL DEFAULT '',
            action varchar(50) NOT NULL DEFAULT '',
            description text NOT NULL,
            context longtext,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY action (action),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Migrate legacy v3.x data structures.
     */
    private static function migrate_legacy_data() {
        global $wpdb;

        // Clean up old orphaned backup options from v1/v2
        $legacy = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE 'ai1wm_inspector_backup_%'
             OR option_name LIKE 'ai1wm_complete_manager_backup_%'",
            ARRAY_A
        );

        foreach ( $legacy as $row ) {
            delete_option( $row['option_name'] );
        }
    }

    /**
     * Default plugin options.
     */
    public static function default_options() {
        return array(
            'backup_limit'          => 5,
            'auto_backup_schedule'  => 'disabled',
            'notifications_enabled' => false,
            'notification_email'    => get_option( 'admin_email', '' ),
            'notification_events'   => array( 'backup_created', 'import_complete', 'backup_failed' ),
            'log_retention_days'    => 60,
        );
    }
}
