<?php
/**
 * Manages All-in-One WP Migration extension versions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Extensions_Manager {

    /** @var string Path to the AI1WM extensions file */
    private $file_path;

    /** @var array Extension prefix => default version map */
    private $extension_versions = array(
        'AI1WMZE' => '',      // Microsoft Azure
        'AI1WMAE' => '1.45',  // Backblaze B2
        'AI1WMVE' => '',      // Backup Plugin
        'AI1WMBE' => '1.48',  // Box
        'AI1WMIE' => '',      // DigitalOcean Spaces
        'AI1WMXE' => '',      // Direct
        'AI1WMDE' => '3.71',  // Dropbox
        'AI1WMTE' => '1.8',   // File
        'AI1WMFE' => '2.84',  // FTP
        'AI1WMCE' => '',      // Google Cloud Storage
        'AI1WMGE' => '2.92',  // Google Drive
        'AI1WMRE' => '',      // Amazon Glacier
        'AI1WMEE' => '',      // Mega
        'AI1WMME' => '4.40',  // Multisite
        'AI1WMOE' => '1.69',  // OneDrive
        'AI1WMPE' => '',      // pCloud
        'AI1WMKE' => '',      // Pro Plugin
        'AI1WMNE' => '1.40',  // S3 Client
        'AI1WMSE' => '3.81',  // Amazon S3
        'AI1WMUE' => '2.65',  // Unlimited
        'AI1WMLE' => '2.71',  // URL
        'AI1WMWE' => '',      // WebDAV
    );

    /** @var array Extension prefix => human-readable name */
    private $extension_names = array(
        'AI1WMZE' => 'Microsoft Azure Extension',
        'AI1WMAE' => 'Backblaze B2 Extension',
        'AI1WMVE' => 'Backup Plugin',
        'AI1WMBE' => 'Box Extension',
        'AI1WMIE' => 'DigitalOcean Spaces Extension',
        'AI1WMXE' => 'Direct Extension',
        'AI1WMDE' => 'Dropbox Extension',
        'AI1WMTE' => 'File Extension',
        'AI1WMFE' => 'FTP Extension',
        'AI1WMCE' => 'Google Cloud Storage Extension',
        'AI1WMGE' => 'Google Drive Extension',
        'AI1WMRE' => 'Amazon Glacier Extension',
        'AI1WMEE' => 'Mega Extension',
        'AI1WMME' => 'Multisite Extension',
        'AI1WMOE' => 'OneDrive Extension',
        'AI1WMPE' => 'pCloud Extension',
        'AI1WMKE' => 'Pro Plugin',
        'AI1WMNE' => 'S3 Client Extension',
        'AI1WMSE' => 'Amazon S3 Extension',
        'AI1WMUE' => 'Unlimited Extension',
        'AI1WMLE' => 'URL Extension',
        'AI1WMWE' => 'WebDAV Extension',
    );

    /** @var array Extension prefix => folder slug in wp-content/plugins/ */
    private $extension_folders = array(
        'AI1WMZE' => 'all-in-one-wp-migration-azure-extension',
        'AI1WMAE' => 'all-in-one-wp-migration-backblaze-extension',
        'AI1WMVE' => 'all-in-one-wp-migration-backup-plugin',
        'AI1WMBE' => 'all-in-one-wp-migration-box-extension',
        'AI1WMIE' => 'all-in-one-wp-migration-digitalocean-extension',
        'AI1WMXE' => 'all-in-one-wp-migration-direct-extension',
        'AI1WMDE' => 'all-in-one-wp-migration-dropbox-extension',
        'AI1WMTE' => 'all-in-one-wp-migration-file-extension',
        'AI1WMFE' => 'all-in-one-wp-migration-ftp-extension',
        'AI1WMCE' => 'all-in-one-wp-migration-google-cloud-extension',
        'AI1WMGE' => 'all-in-one-wp-migration-google-drive-extension',
        'AI1WMRE' => 'all-in-one-wp-migration-glacier-extension',
        'AI1WMEE' => 'all-in-one-wp-migration-mega-extension',
        'AI1WMME' => 'all-in-one-wp-migration-multisite-extension',
        'AI1WMOE' => 'all-in-one-wp-migration-onedrive-extension',
        'AI1WMPE' => 'all-in-one-wp-migration-pcloud-extension',
        'AI1WMKE' => 'all-in-one-wp-migration-pro',
        'AI1WMNE' => 'all-in-one-wp-migration-s3-client-extension',
        'AI1WMSE' => 'all-in-one-wp-migration-s3-extension',
        'AI1WMUE' => 'all-in-one-wp-migration-unlimited-extension',
        'AI1WMLE' => 'all-in-one-wp-migration-url-extension',
        'AI1WMWE' => 'all-in-one-wp-migration-webdav-extension',
    );

    public function __construct() {
        $this->file_path = WP_CONTENT_DIR . '/plugins/all-in-one-wp-migration/lib/model/class-ai1wm-extensions.php';
    }

    /** @return bool */
    public function extensions_file_exists() {
        return file_exists( $this->file_path );
    }

    /** @return string */
    public function get_file_path() {
        return $this->file_path;
    }

    /** @return array All extension prefixes => names */
    public function get_extension_names() {
        return $this->extension_names;
    }

    /** @return array All extension prefixes => default versions */
    public function get_extension_versions() {
        return $this->extension_versions;
    }

    /**
     * Read current versions from the extensions PHP file.
     *
     * @return array prefix => version string
     */
    public function get_current_versions() {
        if ( ! $this->extensions_file_exists() ) {
            return array();
        }

        $content  = file_get_contents( $this->file_path );
        $versions = array();

        foreach ( $this->extension_versions as $prefix => $default ) {
            $found = false;

            // Try to locate section for this extension and extract 'requires' version
            $start = strpos( $content, $prefix . '_PLUGIN_NAME' );
            if ( $start !== false ) {
                $section_end = strpos( $content, 'if ( defined', $start + 10 );
                if ( $section_end === false ) {
                    $section_end = strlen( $content );
                }
                $section = substr( $content, $start, $section_end - $start );
                if ( preg_match( "/'requires'\s*=>\s*['\"]([0-9.]+)['\"]/", $section, $m ) ) {
                    $versions[ $prefix ] = $m[1];
                    $found = true;
                }
            }

            if ( ! $found ) {
                // Fallback: global define
                if ( preg_match( "/define\s*\(\s*['\"]" . $prefix . "_VERSION['\"]\s*,\s*['\"]([0-9.]+)['\"]\s*\)/", $content, $m ) ) {
                    $versions[ $prefix ] = $m[1];
                }
            }
        }

        return $versions;
    }

    /**
     * Check whether an extension folder is installed.
     *
     * @param string $prefix
     * @return bool
     */
    public function is_installed( $prefix ) {
        if ( ! isset( $this->extension_folders[ $prefix ] ) ) {
            return false;
        }
        $folder = WP_PLUGIN_DIR . '/' . $this->extension_folders[ $prefix ];
        return is_dir( $folder );
    }

    /**
     * Get installation status for all extensions.
     *
     * @return array prefix => bool
     */
    public function get_installation_status() {
        $status = array();
        foreach ( array_keys( $this->extension_versions ) as $prefix ) {
            $status[ $prefix ] = $this->is_installed( $prefix );
        }
        return $status;
    }

    /**
     * Backup the current extensions file content and version map.
     *
     * @param string $note
     * @return string Backup option key.
     */
    public function backup( $note = '' ) {
        $versions = $this->get_current_versions();
        $content  = $this->extensions_file_exists()
            ? file_get_contents( $this->file_path )
            : '';

        $key = AI1WM_Manager_Backup_Manager::save_extension_backup( $versions, $content, $note );

        AI1WM_Manager_Activity_Log::log(
            'extension_backup',
            sprintf( __( 'Extension versions backed up (%d extensions).', 'ai1wm-manager' ), count( $versions ) ),
            array( 'backup_key' => $key, 'count' => count( $versions ) )
        );

        AI1WM_Manager_Notifications::send( 'backup_created', array(
            'type'      => 'Extension Backup',
            'count'     => count( $versions ),
            'backup_key' => $key,
        ) );

        return $key;
    }

    /**
     * Update selected extension versions in the extensions file.
     *
     * @param array $updates Map of prefix => new_version (only selected ones).
     * @return array { updated: int, errors: array }
     */
    public function update( $updates ) {
        if ( ! $this->extensions_file_exists() ) {
            return array( 'updated' => 0, 'errors' => array( __( 'Extensions file not found.', 'ai1wm-manager' ) ) );
        }

        $content      = file_get_contents( $this->file_path );
        $update_count = 0;
        $errors       = array();
        $updated_list = array();

        foreach ( $updates as $prefix => $new_version ) {
            $new_version = trim( sanitize_text_field( $new_version ) );
            if ( empty( $new_version ) || ! preg_match( '/^[0-9]+(\.[0-9]+)*$/', $new_version ) ) {
                $errors[] = sprintf( __( 'Invalid version "%s" for %s.', 'ai1wm-manager' ), $new_version, $prefix );
                continue;
            }

            $start = strpos( $content, $prefix . '_PLUGIN_NAME' );
            if ( $start === false ) {
                continue;
            }

            $section_end = strpos( $content, 'if ( defined', $start + 10 );
            if ( $section_end === false ) {
                $section_end = strlen( $content );
            }

            $section         = substr( $content, $start, $section_end - $start );
            $replaced        = 0;
            $updated_section = preg_replace(
                "/'requires'\s*=>\s*['\"]([0-9.]+)['\"]/",
                "'requires' => '{$new_version}'",
                $section,
                -1,
                $replaced
            );

            if ( $replaced > 0 ) {
                $content        = str_replace( $section, $updated_section, $content );
                $update_count  += $replaced;
                $updated_list[] = ( $this->extension_names[ $prefix ] ?? $prefix ) . ' → ' . $new_version;
            }
        }

        if ( $update_count > 0 ) {
            if ( file_put_contents( $this->file_path, $content ) === false ) {
                return array( 'updated' => 0, 'errors' => array( __( 'Failed to write to extensions file.', 'ai1wm-manager' ) ) );
            }

            AI1WM_Manager_Activity_Log::log(
                'extension_update',
                sprintf( __( 'Updated %d extension version(s).', 'ai1wm-manager' ), $update_count ),
                array( 'updated' => $updated_list )
            );
        }

        return array( 'updated' => $update_count, 'errors' => $errors, 'list' => $updated_list );
    }

    /**
     * Revert extensions file from a backup.
     *
     * @param string $backup_key Backup option name (defaults to latest).
     * @return bool
     */
    public function revert( $backup_key = '' ) {
        if ( ! empty( $backup_key ) ) {
            $backup = AI1WM_Manager_Backup_Manager::get( $backup_key );
        } else {
            $backup = AI1WM_Manager_Backup_Manager::get_latest_extension_backup();
        }

        if ( ! $backup || empty( $backup['content'] ) ) {
            return false;
        }

        if ( file_put_contents( $this->file_path, $backup['content'] ) === false ) {
            return false;
        }

        AI1WM_Manager_Activity_Log::log(
            'extension_revert',
            __( 'Extension versions reverted from backup.', 'ai1wm-manager' ),
            array( 'backup_key' => $backup['option_name'] )
        );

        return true;
    }
}
