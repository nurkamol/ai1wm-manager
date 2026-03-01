<?php
/**
 * Manages all plugin backups (extension and settings) stored in wp_options.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Backup_Manager {

    const PREFIX_EXT      = 'ai1wm_manager_backup_';
    const PREFIX_SETTINGS = 'ai1wm_manager_settings_backup_';

    /**
     * Save an extension backup.
     *
     * @param array  $versions   Map of extension prefix => version string.
     * @param string $content    Raw content of the extensions PHP file.
     * @param string $note       Optional user note.
     * @return string The option_name key for this backup.
     */
    public static function save_extension_backup( $versions, $content, $note = '' ) {
        $timestamp = time();
        $key       = self::PREFIX_EXT . $timestamp;

        update_option( $key, array(
            'timestamp'      => $timestamp,
            'type'           => 'extension',
            'versions'       => $versions,
            'content'        => $content,
            'versions_count' => count( $versions ),
            'note'           => sanitize_text_field( $note ),
        ), false );

        self::prune( 'extension' );

        return $key;
    }

    /**
     * Save a settings backup.
     *
     * @param array  $settings   AI1WM settings array.
     * @param string $note       Optional user note.
     * @return string The option_name key for this backup.
     */
    public static function save_settings_backup( $settings, $note = '' ) {
        $timestamp = time();
        $key       = self::PREFIX_SETTINGS . $timestamp;

        update_option( $key, array(
            'timestamp'   => $timestamp,
            'type'        => 'settings',
            'settings'    => $settings,
            'count'       => count( $settings ),
            'note'        => sanitize_text_field( $note ),
        ), false );

        self::prune( 'settings' );

        return $key;
    }

    /**
     * Get all backups, optionally filtered by type.
     *
     * @param string $type 'extension'|'settings'|'all'
     * @return array Sorted newest-first. Each item has option_name added.
     */
    public static function get_all( $type = 'all' ) {
        global $wpdb;

        if ( $type === 'extension' ) {
            $query = $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                 WHERE option_name LIKE %s AND option_name NOT LIKE %s
                 ORDER BY option_name DESC",
                '%' . $wpdb->esc_like( self::PREFIX_EXT ) . '%',
                '%' . $wpdb->esc_like( self::PREFIX_SETTINGS ) . '%'
            );
        } elseif ( $type === 'settings' ) {
            $query = $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 ORDER BY option_name DESC",
                '%' . $wpdb->esc_like( self::PREFIX_SETTINGS ) . '%'
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 ORDER BY option_name DESC",
                '%' . $wpdb->esc_like( 'ai1wm_manager_backup_' ) . '%'
            );
        }

        $rows    = $wpdb->get_results( $query, ARRAY_A );
        $backups = array();

        foreach ( $rows as $row ) {
            $data = maybe_unserialize( $row['option_value'] );
            if ( is_array( $data ) ) {
                $data['option_name'] = $row['option_name'];
                $backups[]           = $data;
            }
        }

        return $backups;
    }

    /**
     * Get a single backup by its option key.
     *
     * @param string $key
     * @return array|null
     */
    public static function get( $key ) {
        $data = get_option( $key );
        if ( ! is_array( $data ) ) {
            return null;
        }
        $data['option_name'] = $key;
        return $data;
    }

    /**
     * Delete a single backup.
     *
     * @param string $key
     * @return bool
     */
    public static function delete( $key ) {
        if ( ! self::is_valid_key( $key ) ) {
            return false;
        }
        return delete_option( $key );
    }

    /**
     * Delete all backups of a given type (or all).
     *
     * @param string $type 'extension'|'settings'|'all'
     * @return int Number deleted.
     */
    public static function delete_all( $type = 'all' ) {
        $backups = self::get_all( $type );
        $count   = 0;
        foreach ( $backups as $backup ) {
            if ( delete_option( $backup['option_name'] ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Update the note on an existing backup.
     *
     * @param string $key
     * @param string $note
     * @return bool
     */
    public static function update_note( $key, $note ) {
        if ( ! self::is_valid_key( $key ) ) {
            return false;
        }
        $data = get_option( $key );
        if ( ! is_array( $data ) ) {
            return false;
        }
        $data['note'] = sanitize_text_field( $note );
        return update_option( $key, $data );
    }

    /**
     * Get the most recent extension backup.
     *
     * @return array|null
     */
    public static function get_latest_extension_backup() {
        $all = self::get_all( 'extension' );
        return ! empty( $all ) ? $all[0] : null;
    }

    /**
     * Validate that a key belongs to this plugin.
     *
     * @param string $key
     * @return bool
     */
    public static function is_valid_key( $key ) {
        return (
            strpos( $key, self::PREFIX_EXT ) === 0 ||
            strpos( $key, self::PREFIX_SETTINGS ) === 0
        );
    }

    /**
     * Remove oldest backups exceeding the configured limit.
     *
     * @param string $type 'extension'|'settings'
     */
    private static function prune( $type ) {
        $options = get_option( 'ai1wm_manager_options', array() );
        $limit   = isset( $options['backup_limit'] ) ? (int) $options['backup_limit'] : 5;
        if ( $limit <= 0 ) {
            return;
        }

        $all = self::get_all( $type );
        if ( count( $all ) > $limit ) {
            $to_delete = array_slice( $all, $limit );
            foreach ( $to_delete as $backup ) {
                delete_option( $backup['option_name'] );
            }
        }
    }
}
