<?php
/**
 * Manages AI1WM plugin settings: export, import, dry-run diff, selective restore.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Settings_Manager {

    /** Option names that are site-specific and should never be exported/imported */
    private $site_specific = array(
        'ai1wm_backups_path',
        'ai1wm_backups_labels',
        'ai1wm_secret_key',
        'ai1wm_manager_version',
        'ai1wm_manager_options',
        'ai1wmfe_ftp_timestamp',
        'ai1wmue_eula_accepted_by',
        '_site_transient_ai1wm_last_check_for_updates',
    );

    /** Option name patterns that are dangerous to import */
    private $dangerous_patterns = array(
        'user_', 'admin_', 'active_plugins', 'template', 'stylesheet', 'home', 'siteurl',
    );

    /** Option name patterns for backup/manager data — skip these */
    private $skip_patterns = array(
        'ai1wm_manager_backup_',
        'ai1wm_manager_settings_backup_',
        'ai1wm_manager_cleaned_duplicates',
        'ai1wm_inspector_backup_',
        'ai1wm_complete_manager_',
    );

    /** Sensitive key fragments for redaction */
    private $sensitive_keys = array(
        'key', 'token', 'secret', 'password', 'pass', 'pwd',
        'access', 'auth', 'credential', 'api_key', 'private',
        'license', 'activation', 'signature', 'hash', 'hostname',
        'username', 'authentication',
    );

    /**
     * Read all transferable AI1WM settings from the database.
     *
     * @param bool $redact Whether to redact sensitive values.
     * @return array option_name => value
     */
    public function get_settings( $redact = false ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 ORDER BY option_name ASC",
                '%ai1wm%'
            ),
            ARRAY_A
        );

        $data = array();
        foreach ( $rows as $row ) {
            if ( $this->should_skip( $row['option_name'] ) ) {
                continue;
            }

            $value = maybe_unserialize( $row['option_value'] );
            $value = $this->clean_updater_data( $row['option_name'], $value );

            if ( $redact ) {
                $value = $this->redact( $value );
            }

            $data[ $row['option_name'] ] = $value;
        }

        return $data;
    }

    /**
     * Build a diff between current settings and a set of incoming settings.
     * Returns arrays of added, removed, and changed keys.
     *
     * @param array $incoming
     * @return array { added: array, removed: array, changed: array }
     */
    public function diff( $incoming ) {
        $current = $this->get_settings( false );
        $added   = array();
        $removed = array();
        $changed = array();

        foreach ( $incoming as $key => $new_val ) {
            if ( ! array_key_exists( $key, $current ) ) {
                $added[ $key ] = $new_val;
            } elseif ( maybe_serialize( $current[ $key ] ) !== maybe_serialize( $new_val ) ) {
                $changed[ $key ] = array(
                    'old' => $current[ $key ],
                    'new' => $new_val,
                );
            }
        }

        foreach ( $current as $key => $val ) {
            if ( ! array_key_exists( $key, $incoming ) ) {
                $removed[ $key ] = $val;
            }
        }

        return compact( 'added', 'removed', 'changed' );
    }

    /**
     * Import settings into the database.
     *
     * @param array $settings_data   Key-value settings to import.
     * @param array $selected_keys   If non-empty, import only these keys.
     * @return array { imported: int, skipped: int, errors: array }
     */
    public function import( $settings_data, $selected_keys = array() ) {
        $imported = 0;
        $skipped  = 0;
        $errors   = array();

        if ( ! empty( $selected_keys ) ) {
            $settings_data = array_intersect_key( $settings_data, array_flip( $selected_keys ) );
        }

        foreach ( $settings_data as $key => $value ) {
            if ( ! is_string( $key ) || strpos( $key, 'ai1wm' ) === false ) {
                $skipped++;
                continue;
            }

            if ( $this->should_skip( $key ) || $this->is_dangerous( $key ) ) {
                $skipped++;
                continue;
            }

            update_option( $key, $value );
            $imported++;
        }

        return compact( 'imported', 'skipped', 'errors' );
    }

    /**
     * Parse a raw JSON string into settings data.
     * Handles both bare and metadata-wrapped formats.
     *
     * @param string $json
     * @return array|WP_Error
     */
    public function parse_json( $json ) {
        $decoded = json_decode( $json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'invalid_json', json_last_error_msg() );
        }

        if ( isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ) {
            return array(
                'settings' => $decoded['settings'],
                'metadata' => $decoded['metadata'] ?? array(),
            );
        }

        return array(
            'settings' => $decoded,
            'metadata' => array(),
        );
    }

    /**
     * Build the export payload array.
     *
     * @param bool $redact
     * @param bool $include_metadata
     * @return array
     */
    public function build_export( $redact = false, $include_metadata = true ) {
        $settings = $this->get_settings( $redact );

        if ( ! $include_metadata ) {
            return $settings;
        }

        return array(
            'metadata' => array(
                'export_date'       => current_time( 'mysql' ),
                'wordpress_version' => get_bloginfo( 'version' ),
                'plugin_version'    => AI1WM_MANAGER_VERSION,
                'site_url'          => get_site_url(),
                'redacted'          => $redact,
            ),
            'settings' => $settings,
        );
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function should_skip( $option_name ) {
        if ( in_array( $option_name, $this->site_specific, true ) ) {
            return true;
        }
        foreach ( $this->skip_patterns as $pattern ) {
            if ( strpos( $option_name, $pattern ) !== false ) {
                return true;
            }
        }
        // Skip anything containing '_backup_' as a substring
        if ( strpos( $option_name, '_backup_' ) !== false ) {
            return true;
        }
        return false;
    }

    private function is_dangerous( $option_name ) {
        foreach ( $this->dangerous_patterns as $pattern ) {
            if ( strpos( $option_name, $pattern ) !== false ) {
                return true;
            }
        }
        return false;
    }

    private function clean_updater_data( $name, $value ) {
        if ( $name === 'ai1wm_updater' && is_array( $value ) && ! empty( $value ) ) {
            $first = reset( $value );
            if ( is_array( $first ) && isset( $first['name'] ) ) {
                return array(); // Strip bloated extension marketing metadata
            }
        }
        return $value;
    }

    private function redact( $data ) {
        if ( is_array( $data ) ) {
            foreach ( $data as $key => $value ) {
                if ( ( is_array( $value ) || is_object( $value ) ) ) {
                    $data[ $key ] = $this->redact( $value );
                } elseif ( is_string( $key ) && $this->is_sensitive_key( $key ) ) {
                    $data[ $key ] = '[REDACTED]';
                }
            }
        }
        return $data;
    }

    private function is_sensitive_key( $key ) {
        $k = strtolower( $key );
        foreach ( $this->sensitive_keys as $pattern ) {
            if ( strpos( $k, $pattern ) !== false ) {
                return true;
            }
        }
        return false;
    }
}
