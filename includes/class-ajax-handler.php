<?php
/**
 * AJAX endpoint handler. All actions return JSON via wp_send_json_*.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Ajax_Handler {

    /** @var AI1WM_Manager_Extensions_Manager */
    private $ext;

    /** @var AI1WM_Manager_Settings_Manager */
    private $settings;

    public function __construct() {
        $this->ext      = new AI1WM_Manager_Extensions_Manager();
        $this->settings = new AI1WM_Manager_Settings_Manager();
    }

    /**
     * Register all AJAX actions (called from Core).
     */
    public function register_hooks() {
        $actions = array(
            'backup_extensions',
            'update_extensions',
            'revert_extensions',
            'export_settings',
            'import_settings',
            'dry_run_import',
            'backup_settings',
            'restore_settings',
            'remove_backup',
            'remove_all_backups',
            'update_backup_note',
            'download_backup',
            'save_options',
            'clear_activity_log',
            'get_activity_log',
        );

        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_ai1wm_manager_' . $action, array( $this, 'handle_' . $action ) );
        }
    }

    // ── Security helper ──────────────────────────────────────────────────────

    private function verify() {
        check_ajax_referer( AI1WM_MANAGER_NONCE, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai1wm-manager' ) ), 403 );
        }
    }

    // ── Extension actions ────────────────────────────────────────────────────

    public function handle_backup_extensions() {
        $this->verify();
        $note = isset( $_POST['note'] ) ? sanitize_text_field( $_POST['note'] ) : '';
        $key  = $this->ext->backup( $note );
        wp_send_json_success( array( 'message' => __( 'Extension versions backed up successfully.', 'ai1wm-manager' ), 'key' => $key ) );
    }

    public function handle_update_extensions() {
        $this->verify();

        if ( empty( $_POST['updates'] ) || ! is_array( $_POST['updates'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No extensions selected.', 'ai1wm-manager' ) ) );
        }

        $updates = array();
        foreach ( $_POST['updates'] as $prefix => $version ) {
            // sanitize_key() lowercases; strtoupper() restores the uppercase prefix
            // required to match 'AI1WMUE_PLUGIN_NAME' etc. in the extensions file.
            $updates[ strtoupper( sanitize_key( $prefix ) ) ] = sanitize_text_field( $version );
        }

        // Compatibility check: warn about not-installed extensions
        $warnings = array();
        foreach ( array_keys( $updates ) as $prefix ) {
            if ( ! $this->ext->is_installed( $prefix ) ) {
                $names    = $this->ext->get_extension_names();
                $warnings[] = sprintf( __( '%s folder not found in plugins directory.', 'ai1wm-manager' ), $names[ $prefix ] ?? $prefix );
            }
        }

        $result = $this->ext->update( $updates );

        wp_send_json_success( array(
            'message'  => sprintf( __( '%d extension(s) updated successfully.', 'ai1wm-manager' ), $result['updated'] ),
            'updated'  => $result['updated'],
            'errors'   => $result['errors'],
            'warnings' => $warnings,
            'list'     => $result['list'] ?? array(),
        ) );
    }

    public function handle_revert_extensions() {
        $this->verify();
        $key    = isset( $_POST['backup_key'] ) ? sanitize_text_field( $_POST['backup_key'] ) : '';
        $result = $this->ext->revert( $key );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Extension versions reverted successfully.', 'ai1wm-manager' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Revert failed: no valid backup found.', 'ai1wm-manager' ) ) );
        }
    }

    // ── Settings actions ─────────────────────────────────────────────────────

    public function handle_export_settings() {
        $this->verify();

        $redact   = ! empty( $_POST['redact'] );
        $metadata = ! empty( $_POST['include_metadata'] );

        $payload  = $this->settings->build_export( $redact, $metadata );
        $json     = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        $filename = 'ai1wm-settings-' . date( 'Y-m-d-H-i-s' ) . '.json';

        AI1WM_Manager_Activity_Log::log( 'settings_export', __( 'AI1WM settings exported.', 'ai1wm-manager' ), array( 'redacted' => $redact ) );
        AI1WM_Manager_Notifications::send( 'export_complete', array( 'type' => 'Settings Export', 'redacted' => $redact ? 'Yes' : 'No' ) );

        // Return as base64 so JS can trigger download without a page navigation
        wp_send_json_success( array(
            'filename' => $filename,
            'content'  => base64_encode( $json ),
            'message'  => __( 'Settings exported successfully.', 'ai1wm-manager' ),
        ) );
    }

    public function handle_import_settings() {
        $this->verify();

        $parsed = $this->parse_upload();
        if ( is_wp_error( $parsed ) ) {
            wp_send_json_error( array( 'message' => $parsed->get_error_message() ) );
        }

        $settings_data = $parsed['settings'];
        $metadata      = $parsed['metadata'];
        $selected_keys = isset( $_POST['selected_keys'] ) ? (array) $_POST['selected_keys'] : array();

        // Auto-backup before import
        $all_settings = $this->settings->get_settings( false );
        AI1WM_Manager_Backup_Manager::save_settings_backup(
            $all_settings,
            __( 'Auto-backup before import', 'ai1wm-manager' )
        );

        $result = $this->settings->import( $settings_data, $selected_keys );

        $warning = '';
        if ( ! empty( $metadata['site_url'] ) && $metadata['site_url'] !== get_site_url() ) {
            $warning = sprintf( __( 'Note: This backup was created on %s.', 'ai1wm-manager' ), $metadata['site_url'] );
        }

        AI1WM_Manager_Activity_Log::log(
            'settings_import',
            sprintf( __( 'Settings imported: %d imported, %d skipped.', 'ai1wm-manager' ), $result['imported'], $result['skipped'] ),
            array( 'source_site' => $metadata['site_url'] ?? '' )
        );
        AI1WM_Manager_Notifications::send( 'import_complete', array(
            'imported' => $result['imported'],
            'skipped'  => $result['skipped'],
        ) );

        wp_send_json_success( array(
            'message' => sprintf( __( 'Import complete: %d settings imported, %d skipped.', 'ai1wm-manager' ), $result['imported'], $result['skipped'] ),
            'warning' => $warning,
        ) );
    }

    public function handle_dry_run_import() {
        $this->verify();

        $parsed = $this->parse_upload();
        if ( is_wp_error( $parsed ) ) {
            wp_send_json_error( array( 'message' => $parsed->get_error_message() ) );
        }

        $diff = $this->settings->diff( $parsed['settings'] );

        AI1WM_Manager_Activity_Log::log( 'settings_dry_run', __( 'Dry-run import preview generated.', 'ai1wm-manager' ) );

        wp_send_json_success( array(
            'diff'    => $diff,
            'message' => sprintf(
                __( 'Preview: %d to add, %d to change, %d to remove.', 'ai1wm-manager' ),
                count( $diff['added'] ),
                count( $diff['changed'] ),
                count( $diff['removed'] )
            ),
        ) );
    }

    public function handle_backup_settings() {
        $this->verify();
        $note     = isset( $_POST['note'] ) ? sanitize_text_field( $_POST['note'] ) : '';
        $settings = $this->settings->get_settings( false );
        $key      = AI1WM_Manager_Backup_Manager::save_settings_backup( $settings, $note );

        AI1WM_Manager_Activity_Log::log( 'settings_backup', __( 'Settings backup created manually.', 'ai1wm-manager' ), array( 'key' => $key ) );
        AI1WM_Manager_Notifications::send( 'backup_created', array( 'type' => 'Manual Settings Backup', 'key' => $key ) );

        wp_send_json_success( array( 'message' => __( 'Settings backup created successfully.', 'ai1wm-manager' ), 'key' => $key ) );
    }

    public function handle_restore_settings() {
        $this->verify();

        $backup_key    = isset( $_POST['backup_key'] ) ? sanitize_text_field( $_POST['backup_key'] ) : '';
        $selected_keys = isset( $_POST['selected_keys'] ) ? (array) $_POST['selected_keys'] : array();

        if ( ! AI1WM_Manager_Backup_Manager::is_valid_key( $backup_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid backup key.', 'ai1wm-manager' ) ) );
        }

        $backup = AI1WM_Manager_Backup_Manager::get( $backup_key );
        if ( ! $backup || empty( $backup['settings'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Backup not found or has no settings.', 'ai1wm-manager' ) ) );
        }

        // Auto-backup current settings first
        $current = $this->settings->get_settings( false );
        AI1WM_Manager_Backup_Manager::save_settings_backup( $current, __( 'Auto-backup before restore', 'ai1wm-manager' ) );

        $result = $this->settings->import( $backup['settings'], $selected_keys );

        AI1WM_Manager_Activity_Log::log(
            'settings_restore',
            sprintf( __( 'Settings restored from backup: %d imported.', 'ai1wm-manager' ), $result['imported'] ),
            array( 'backup_key' => $backup_key )
        );

        wp_send_json_success( array(
            'message' => sprintf( __( 'Restore complete: %d settings restored.', 'ai1wm-manager' ), $result['imported'] ),
        ) );
    }

    // ── Backup management actions ────────────────────────────────────────────

    public function handle_remove_backup() {
        $this->verify();
        $key = isset( $_POST['backup_key'] ) ? sanitize_text_field( $_POST['backup_key'] ) : '';

        if ( ! AI1WM_Manager_Backup_Manager::is_valid_key( $key ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid backup key.', 'ai1wm-manager' ) ) );
        }

        if ( AI1WM_Manager_Backup_Manager::delete( $key ) ) {
            AI1WM_Manager_Activity_Log::log( 'backup_deleted', __( 'Backup deleted.', 'ai1wm-manager' ), array( 'key' => $key ) );
            wp_send_json_success( array( 'message' => __( 'Backup removed successfully.', 'ai1wm-manager' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to remove backup.', 'ai1wm-manager' ) ) );
        }
    }

    public function handle_remove_all_backups() {
        $this->verify();
        $type  = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 'all';
        $count = AI1WM_Manager_Backup_Manager::delete_all( $type );

        AI1WM_Manager_Activity_Log::log( 'backups_cleared', sprintf( __( '%d backup(s) deleted.', 'ai1wm-manager' ), $count ), array( 'type' => $type ) );
        wp_send_json_success( array( 'message' => sprintf( __( '%d backup(s) removed.', 'ai1wm-manager' ), $count ), 'count' => $count ) );
    }

    public function handle_update_backup_note() {
        $this->verify();
        $key  = isset( $_POST['backup_key'] ) ? sanitize_text_field( $_POST['backup_key'] ) : '';
        $note = isset( $_POST['note'] ) ? sanitize_text_field( $_POST['note'] ) : '';

        if ( AI1WM_Manager_Backup_Manager::update_note( $key, $note ) ) {
            wp_send_json_success( array( 'message' => __( 'Note saved.', 'ai1wm-manager' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to save note.', 'ai1wm-manager' ) ) );
        }
    }

    public function handle_download_backup() {
        $this->verify();
        $key    = isset( $_POST['backup_key'] ) ? sanitize_text_field( $_POST['backup_key'] ) : '';
        $backup = AI1WM_Manager_Backup_Manager::get( $key );

        if ( ! $backup ) {
            wp_send_json_error( array( 'message' => __( 'Backup not found.', 'ai1wm-manager' ) ) );
        }

        $type     = $backup['type'] ?? 'backup';
        $filename = 'ai1wm-' . $type . '-' . date( 'Y-m-d-H-i-s', $backup['timestamp'] ?? time() ) . '.json';
        $json     = wp_json_encode( $backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

        wp_send_json_success( array(
            'filename' => $filename,
            'content'  => base64_encode( $json ),
            'message'  => __( 'Backup ready for download.', 'ai1wm-manager' ),
        ) );
    }

    // ── Plugin options ───────────────────────────────────────────────────────

    public function handle_save_options() {
        $this->verify();

        $raw     = isset( $_POST['options'] ) ? (array) $_POST['options'] : array();
        $current = get_option( 'ai1wm_manager_options', AI1WM_Manager_Installer::default_options() );

        $sanitized = array(
            'backup_limit'          => max( 1, min( 50, (int) ( $raw['backup_limit'] ?? 5 ) ) ),
            'auto_backup_schedule'  => in_array( $raw['auto_backup_schedule'] ?? '', array( 'disabled', 'daily', 'weekly', 'monthly' ), true )
                                        ? $raw['auto_backup_schedule']
                                        : 'disabled',
            'notifications_enabled' => ! empty( $raw['notifications_enabled'] ),
            'notification_email'    => sanitize_email( $raw['notification_email'] ?? get_option( 'admin_email' ) ),
            'notification_events'   => array_intersect(
                (array) ( $raw['notification_events'] ?? array() ),
                array_keys( AI1WM_Manager_Notifications::EVENTS )
            ),
            'log_retention_days'    => (int) ( $raw['log_retention_days'] ?? 60 ),
        );

        $merged = array_merge( $current, $sanitized );
        update_option( 'ai1wm_manager_options', $merged );

        // Update cron schedule
        AI1WM_Manager_Scheduler::update_schedule( $merged['auto_backup_schedule'] );

        // Prune logs if retention changed
        if ( $merged['log_retention_days'] > 0 ) {
            AI1WM_Manager_Activity_Log::prune( $merged['log_retention_days'] );
        }

        AI1WM_Manager_Activity_Log::log( 'options_saved', __( 'Plugin options updated.', 'ai1wm-manager' ) );

        wp_send_json_success( array(
            'message'   => __( 'Settings saved successfully.', 'ai1wm-manager' ),
            'next_run'  => AI1WM_Manager_Scheduler::get_next_run(),
        ) );
    }

    // ── Activity log actions ─────────────────────────────────────────────────

    public function handle_clear_activity_log() {
        $this->verify();
        AI1WM_Manager_Activity_Log::clear_all();
        wp_send_json_success( array( 'message' => __( 'Activity log cleared.', 'ai1wm-manager' ) ) );
    }

    public function handle_get_activity_log() {
        $this->verify();

        $args = array(
            'per_page'  => (int) ( $_POST['per_page'] ?? 20 ),
            'page'      => (int) ( $_POST['page'] ?? 1 ),
            'action'    => sanitize_key( $_POST['filter_action'] ?? '' ),
            'date_from' => sanitize_text_field( $_POST['date_from'] ?? '' ),
            'date_to'   => sanitize_text_field( $_POST['date_to'] ?? '' ),
        );

        $result = AI1WM_Manager_Activity_Log::get_entries( $args );
        wp_send_json_success( $result );
    }

    // ── Upload helper ────────────────────────────────────────────────────────

    private function parse_upload() {
        if ( empty( $_FILES['json_file']['tmp_name'] ) ) {
            return new WP_Error( 'no_file', __( 'No file uploaded.', 'ai1wm-manager' ) );
        }

        $ext = strtolower( pathinfo( $_FILES['json_file']['name'], PATHINFO_EXTENSION ) );
        if ( $ext !== 'json' ) {
            return new WP_Error( 'bad_type', __( 'Please upload a .json file.', 'ai1wm-manager' ) );
        }

        if ( $_FILES['json_file']['size'] > 10 * 1024 * 1024 ) {
            return new WP_Error( 'too_large', __( 'File exceeds 10 MB limit.', 'ai1wm-manager' ) );
        }

        $json = file_get_contents( $_FILES['json_file']['tmp_name'] );
        if ( $json === false ) {
            return new WP_Error( 'read_error', __( 'Could not read uploaded file.', 'ai1wm-manager' ) );
        }

        return $this->settings->parse_json( $json );
    }
}
