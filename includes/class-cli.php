<?php
/**
 * WP-CLI integration: wp ai1wm-manager <subcommand>
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/**
 * Manage All-in-One WP Migration extensions and settings from the command line.
 */
class AI1WM_Manager_CLI {

    /**
     * List all registered extensions and their current versions.
     *
     * ## EXAMPLES
     *
     *     wp ai1wm-manager list-extensions
     *
     * @subcommand list-extensions
     */
    public function list_extensions( $args, $assoc_args ) {
        $mgr      = new AI1WM_Manager_Extensions_Manager();
        $current  = $mgr->get_current_versions();
        $names    = $mgr->get_extension_names();
        $defaults = $mgr->get_extension_versions();

        $rows = array();
        foreach ( $names as $prefix => $name ) {
            $rows[] = array(
                'Prefix'     => $prefix,
                'Name'       => $name,
                'Version'    => $current[ $prefix ] ?? '(not found)',
                'Installed'  => $mgr->is_installed( $prefix ) ? 'Yes' : 'No',
            );
        }

        WP_CLI\Utils\format_items( 'table', $rows, array( 'Prefix', 'Name', 'Version', 'Installed' ) );
    }

    /**
     * Backup current extension versions.
     *
     * ## OPTIONS
     *
     * [--note=<note>]
     * : Optional label for this backup.
     *
     * ## EXAMPLES
     *
     *     wp ai1wm-manager backup-extensions
     *     wp ai1wm-manager backup-extensions --note="Before site migration"
     *
     * @subcommand backup-extensions
     */
    public function backup_extensions( $args, $assoc_args ) {
        $note = $assoc_args['note'] ?? '';
        $mgr  = new AI1WM_Manager_Extensions_Manager();
        $key  = $mgr->backup( $note );
        WP_CLI::success( sprintf( 'Extension backup created: %s', $key ) );
    }

    /**
     * Backup current AI1WM settings.
     *
     * ## OPTIONS
     *
     * [--note=<note>]
     * : Optional label for this backup.
     *
     * ## EXAMPLES
     *
     *     wp ai1wm-manager backup-settings
     *     wp ai1wm-manager backup-settings --note="Weekly backup"
     *
     * @subcommand backup-settings
     */
    public function backup_settings( $args, $assoc_args ) {
        $note     = $assoc_args['note'] ?? '';
        $mgr      = new AI1WM_Manager_Settings_Manager();
        $settings = $mgr->get_settings( false );
        $key      = AI1WM_Manager_Backup_Manager::save_settings_backup( $settings, $note );
        WP_CLI::success( sprintf( 'Settings backup created: %s (%d settings)', $key, count( $settings ) ) );
    }

    /**
     * Export AI1WM settings to stdout or a file.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Output file path. If omitted, output goes to stdout.
     *
     * [--redact]
     * : Redact sensitive values.
     *
     * [--no-metadata]
     * : Omit export metadata block.
     *
     * ## EXAMPLES
     *
     *     wp ai1wm-manager export-settings --file=/tmp/ai1wm-export.json
     *     wp ai1wm-manager export-settings --redact
     *
     * @subcommand export-settings
     */
    public function export_settings( $args, $assoc_args ) {
        $redact   = isset( $assoc_args['redact'] );
        $metadata = ! isset( $assoc_args['no-metadata'] );
        $file     = $assoc_args['file'] ?? '';

        $mgr     = new AI1WM_Manager_Settings_Manager();
        $payload = $mgr->build_export( $redact, $metadata );
        $json    = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

        if ( ! empty( $file ) ) {
            if ( file_put_contents( $file, $json ) === false ) {
                WP_CLI::error( sprintf( 'Could not write to file: %s', $file ) );
            }
            WP_CLI::success( sprintf( 'Settings exported to %s', $file ) );
        } else {
            echo $json . PHP_EOL;
        }
    }

    /**
     * List all backups.
     *
     * ## OPTIONS
     *
     * [--type=<type>]
     * : Filter by type: all, extension, settings. Default: all.
     *
     * ## EXAMPLES
     *
     *     wp ai1wm-manager list-backups
     *     wp ai1wm-manager list-backups --type=settings
     *
     * @subcommand list-backups
     */
    public function list_backups( $args, $assoc_args ) {
        $type    = $assoc_args['type'] ?? 'all';
        $backups = AI1WM_Manager_Backup_Manager::get_all( $type );

        if ( empty( $backups ) ) {
            WP_CLI::warning( 'No backups found.' );
            return;
        }

        $rows = array();
        foreach ( $backups as $b ) {
            $rows[] = array(
                'Key'       => $b['option_name'],
                'Type'      => ucfirst( $b['type'] ?? 'unknown' ),
                'Date'      => date( 'Y-m-d H:i:s', $b['timestamp'] ?? 0 ),
                'Note'      => $b['note'] ?? '',
            );
        }

        WP_CLI\Utils\format_items( 'table', $rows, array( 'Key', 'Type', 'Date', 'Note' ) );
    }

    /**
     * View recent activity log entries.
     *
     * ## OPTIONS
     *
     * [--count=<n>]
     * : Number of entries to show. Default: 20.
     *
     * ## EXAMPLES
     *
     *     wp ai1wm-manager activity-log
     *     wp ai1wm-manager activity-log --count=50
     *
     * @subcommand activity-log
     */
    public function activity_log( $args, $assoc_args ) {
        $count  = (int) ( $assoc_args['count'] ?? 20 );
        $result = AI1WM_Manager_Activity_Log::get_entries( array( 'per_page' => $count, 'page' => 1 ) );

        if ( empty( $result['items'] ) ) {
            WP_CLI::warning( 'Activity log is empty.' );
            return;
        }

        $rows = array();
        foreach ( $result['items'] as $item ) {
            $rows[] = array(
                'Date'        => $item['created_at'],
                'User'        => $item['user_login'],
                'Action'      => $item['action'],
                'Description' => $item['description'],
            );
        }

        WP_CLI\Utils\format_items( 'table', $rows, array( 'Date', 'User', 'Action', 'Description' ) );
        WP_CLI::log( sprintf( 'Showing %d of %d entries.', count( $rows ), $result['total'] ) );
    }
}

WP_CLI::add_command( 'ai1wm-manager', 'AI1WM_Manager_CLI' );
