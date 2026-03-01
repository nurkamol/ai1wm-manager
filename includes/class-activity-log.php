<?php
/**
 * Activity log — records all plugin actions to a custom DB table.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Activity_Log {

    /** @var string Table name (without prefix) */
    const TABLE = 'ai1wm_manager_logs';

    /**
     * Write a log entry.
     *
     * @param string $action      Short machine-readable action key (e.g. 'extension_update').
     * @param string $description Human-readable description.
     * @param array  $context     Optional extra data stored as JSON.
     */
    public static function log( $action, $description, $context = array() ) {
        global $wpdb;

        $user     = wp_get_current_user();
        $table    = $wpdb->prefix . self::TABLE;

        $wpdb->insert(
            $table,
            array(
                'user_id'     => $user->ID,
                'user_login'  => $user->user_login,
                'action'      => sanitize_key( $action ),
                'description' => sanitize_text_field( $description ),
                'context'     => wp_json_encode( $context ),
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Get log entries with optional filters.
     *
     * @param array $args {
     *     @type int    $per_page   Entries per page. Default 20.
     *     @type int    $page       Current page. Default 1.
     *     @type string $action     Filter by action key.
     *     @type int    $user_id    Filter by user ID.
     *     @type string $date_from  MySQL date string (inclusive).
     *     @type string $date_to    MySQL date string (inclusive).
     * }
     * @return array { items: array, total: int }
     */
    public static function get_entries( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'per_page'  => 20,
            'page'      => 1,
            'action'    => '',
            'user_id'   => 0,
            'date_from' => '',
            'date_to'   => '',
        );
        $args   = wp_parse_args( $args, $defaults );
        $table  = $wpdb->prefix . self::TABLE;
        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['action'] ) ) {
            $where[]  = 'action = %s';
            $values[] = $args['action'];
        }
        if ( ! empty( $args['user_id'] ) ) {
            $where[]  = 'user_id = %d';
            $values[] = (int) $args['user_id'];
        }
        if ( ! empty( $args['date_from'] ) ) {
            $where[]  = 'created_at >= %s';
            $values[] = $args['date_from'] . ' 00:00:00';
        }
        if ( ! empty( $args['date_to'] ) ) {
            $where[]  = 'created_at <= %s';
            $values[] = $args['date_to'] . ' 23:59:59';
        }

        $where_sql = implode( ' AND ', $where );
        $offset    = ( $args['page'] - 1 ) * $args['per_page'];

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $data_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";

        if ( ! empty( $values ) ) {
            $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) );
            $items = $wpdb->get_results(
                $wpdb->prepare( $data_sql, array_merge( $values, array( $args['per_page'], $offset ) ) ),
                ARRAY_A
            );
        } else {
            $total = (int) $wpdb->get_var( $count_sql );
            $items = $wpdb->get_results(
                $wpdb->prepare( $data_sql, $args['per_page'], $offset ),
                ARRAY_A
            );
        }

        return array( 'items' => $items ?: array(), 'total' => $total );
    }

    /**
     * Get distinct action types for filter dropdowns.
     */
    public static function get_action_types() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->get_col( "SELECT DISTINCT action FROM {$table} ORDER BY action ASC" ) ?: array();
    }

    /**
     * Delete all log entries.
     */
    public static function clear_all() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $wpdb->query( "TRUNCATE TABLE {$table}" );
    }

    /**
     * Prune entries older than $days days.
     */
    public static function prune( $days ) {
        global $wpdb;
        $days  = (int) $days;
        if ( $days <= 0 ) {
            return;
        }
        $table = $wpdb->prefix . self::TABLE;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
}
