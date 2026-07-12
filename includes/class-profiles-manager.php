<?php
/**
 * Version Profiles — save a named set of extension versions and re-apply it later.
 *
 * Profiles are stored in a single wp_option (`ai1wm_manager_profiles`) as an
 * associative array keyed by a generated profile id:
 *
 *   'profile_1700000000' => array(
 *       'id'         => 'profile_1700000000',
 *       'name'       => 'Prod known-good',
 *       'versions'   => array( 'AI1WMUE' => '2.65', ... ),
 *       'created_at' => 1700000000,
 *   )
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Profiles_Manager {

    const OPTION = 'ai1wm_manager_profiles';
    const MAX_PROFILES = 25;

    /**
     * Get all saved profiles, newest first.
     *
     * @return array List of profile arrays.
     */
    public static function get_all() {
        $profiles = get_option( self::OPTION, array() );
        if ( ! is_array( $profiles ) ) {
            return array();
        }

        // Sort newest first by created_at.
        uasort( $profiles, function ( $a, $b ) {
            return ( $b['created_at'] ?? 0 ) <=> ( $a['created_at'] ?? 0 );
        } );

        return array_values( $profiles );
    }

    /**
     * Get a single profile by id.
     *
     * @param string $id
     * @return array|null
     */
    public static function get( $id ) {
        $profiles = get_option( self::OPTION, array() );
        return isset( $profiles[ $id ] ) ? $profiles[ $id ] : null;
    }

    /**
     * Save a new profile capturing a version map.
     *
     * @param string $name     Human-readable label.
     * @param array  $versions Map of prefix => version string.
     * @return array|WP_Error The saved profile, or WP_Error on failure.
     */
    public static function save( $name, $versions ) {
        $name = trim( sanitize_text_field( $name ) );
        if ( $name === '' ) {
            return new WP_Error( 'empty_name', __( 'Please provide a profile name.', 'ai1wm-manager' ) );
        }

        // Keep only well-formed prefix => version pairs.
        $clean = array();
        foreach ( (array) $versions as $prefix => $version ) {
            $prefix  = strtoupper( sanitize_key( $prefix ) );
            $version = trim( sanitize_text_field( $version ) );
            if ( $version !== '' && preg_match( '/^[0-9]+(\.[0-9]+)*$/', $version ) ) {
                $clean[ $prefix ] = $version;
            }
        }

        if ( empty( $clean ) ) {
            return new WP_Error( 'no_versions', __( 'No valid extension versions to save.', 'ai1wm-manager' ) );
        }

        $profiles = get_option( self::OPTION, array() );
        if ( ! is_array( $profiles ) ) {
            $profiles = array();
        }

        if ( count( $profiles ) >= self::MAX_PROFILES ) {
            return new WP_Error( 'limit', sprintf( __( 'Profile limit reached (%d). Delete an old profile first.', 'ai1wm-manager' ), self::MAX_PROFILES ) );
        }

        $id      = 'profile_' . time() . '_' . wp_rand( 100, 999 );
        $profile = array(
            'id'         => $id,
            'name'       => $name,
            'versions'   => $clean,
            'created_at' => time(),
        );

        $profiles[ $id ] = $profile;
        update_option( self::OPTION, $profiles, false );

        AI1WM_Manager_Activity_Log::log(
            'profile_saved',
            sprintf( __( 'Version profile "%s" saved (%d extensions).', 'ai1wm-manager' ), $name, count( $clean ) ),
            array( 'profile_id' => $id )
        );

        return $profile;
    }

    /**
     * Delete a profile.
     *
     * @param string $id
     * @return bool
     */
    public static function delete( $id ) {
        $profiles = get_option( self::OPTION, array() );
        if ( ! is_array( $profiles ) || ! isset( $profiles[ $id ] ) ) {
            return false;
        }

        $name = $profiles[ $id ]['name'] ?? $id;
        unset( $profiles[ $id ] );
        update_option( self::OPTION, $profiles, false );

        AI1WM_Manager_Activity_Log::log(
            'profile_deleted',
            sprintf( __( 'Version profile "%s" deleted.', 'ai1wm-manager' ), $name ),
            array( 'profile_id' => $id )
        );

        return true;
    }

    /**
     * Apply a profile to the extensions file (auto-backs up first).
     *
     * @param string $id
     * @return array|WP_Error { updated:int, errors:array, list:array } or WP_Error.
     */
    public static function apply( $id ) {
        $profile = self::get( $id );
        if ( ! $profile || empty( $profile['versions'] ) ) {
            return new WP_Error( 'not_found', __( 'Profile not found or empty.', 'ai1wm-manager' ) );
        }

        $ext = new AI1WM_Manager_Extensions_Manager();
        if ( ! $ext->extensions_file_exists() ) {
            return new WP_Error( 'no_file', __( 'Extensions file not found.', 'ai1wm-manager' ) );
        }

        // Safety backup before applying.
        $ext->backup( sprintf( __( 'Auto-backup before applying profile "%s"', 'ai1wm-manager' ), $profile['name'] ) );

        $result = $ext->update( $profile['versions'] );

        AI1WM_Manager_Activity_Log::log(
            'profile_applied',
            sprintf( __( 'Version profile "%1$s" applied: %2$d extension(s) updated.', 'ai1wm-manager' ), $profile['name'], $result['updated'] ),
            array( 'profile_id' => $id )
        );

        return $result;
    }
}
