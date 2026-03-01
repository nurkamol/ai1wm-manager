<?php
/**
 * WP-Cron based scheduler for automatic settings backups.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Scheduler {

    const HOOK = 'ai1wm_manager_scheduled_backup';

    /**
     * Register hooks (called from Core).
     */
    public function register_hooks() {
        add_action( self::HOOK, array( $this, 'run_scheduled_backup' ) );
        add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
    }

    /**
     * Add custom cron intervals.
     */
    public function add_schedules( $schedules ) {
        if ( ! isset( $schedules['weekly'] ) ) {
            $schedules['weekly'] = array(
                'interval' => WEEK_IN_SECONDS,
                'display'  => __( 'Once Weekly', 'ai1wm-manager' ),
            );
        }
        if ( ! isset( $schedules['monthly'] ) ) {
            $schedules['monthly'] = array(
                'interval' => 30 * DAY_IN_SECONDS,
                'display'  => __( 'Once Monthly', 'ai1wm-manager' ),
            );
        }
        return $schedules;
    }

    /**
     * Execute the scheduled backup.
     */
    public function run_scheduled_backup() {
        $settings_manager = new AI1WM_Manager_Settings_Manager();
        $settings         = $settings_manager->get_settings( false );

        $key = AI1WM_Manager_Backup_Manager::save_settings_backup(
            $settings,
            __( 'Auto-backup (scheduled)', 'ai1wm-manager' )
        );

        if ( $key ) {
            AI1WM_Manager_Activity_Log::log(
                'scheduled_backup',
                __( 'Automatic scheduled settings backup created.', 'ai1wm-manager' ),
                array( 'backup_key' => $key )
            );
            AI1WM_Manager_Notifications::send( 'backup_created', array(
                'type'       => 'Scheduled Settings Backup',
                'backup_key' => $key,
                'count'      => count( $settings ),
            ) );
        } else {
            AI1WM_Manager_Notifications::send( 'backup_failed', array(
                'type'   => 'Scheduled Settings Backup',
                'reason' => 'Could not save backup to database.',
            ) );
        }
    }

    /**
     * Update the cron schedule based on plugin options.
     * Called when options are saved.
     *
     * @param string $schedule 'disabled'|'daily'|'weekly'|'monthly'
     */
    public static function update_schedule( $schedule ) {
        // Clear existing schedule
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
        }

        if ( $schedule === 'disabled' || empty( $schedule ) ) {
            return;
        }

        // Map option value to WP cron recurrence
        $recurrences = array(
            'daily'   => 'daily',
            'weekly'  => 'weekly',
            'monthly' => 'monthly',
        );

        $recurrence = $recurrences[ $schedule ] ?? 'daily';

        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time(), $recurrence, self::HOOK );
        }
    }

    /**
     * Get human-readable next run time.
     *
     * @return string
     */
    public static function get_next_run() {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( ! $timestamp ) {
            return __( 'Not scheduled', 'ai1wm-manager' );
        }
        return sprintf(
            __( '%s (%s from now)', 'ai1wm-manager' ),
            date_i18n( 'Y-m-d H:i:s', $timestamp ),
            human_time_diff( time(), $timestamp )
        );
    }
}
