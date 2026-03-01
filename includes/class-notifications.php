<?php
/**
 * Email notification system.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Notifications {

    /**
     * Supported event keys.
     */
    const EVENTS = array(
        'backup_created'  => 'Backup Created',
        'import_complete' => 'Settings Import Complete',
        'backup_failed'   => 'Backup Failed',
        'export_complete' => 'Settings Export Complete',
    );

    /**
     * Send a notification email for an event.
     *
     * @param string $event One of the EVENTS keys.
     * @param array  $data  Context data for the email body.
     */
    public static function send( $event, $data = array() ) {
        $options = get_option( 'ai1wm_manager_options', array() );

        if ( empty( $options['notifications_enabled'] ) ) {
            return;
        }

        $events = isset( $options['notification_events'] ) ? (array) $options['notification_events'] : array();
        if ( ! in_array( $event, $events, true ) ) {
            return;
        }

        $to = isset( $options['notification_email'] ) && ! empty( $options['notification_email'] )
            ? $options['notification_email']
            : get_option( 'admin_email' );

        $label   = isset( self::EVENTS[ $event ] ) ? self::EVENTS[ $event ] : $event;
        $subject = sprintf( '[%s] AI1WM Manager: %s', get_bloginfo( 'name' ), $label );
        $body    = self::build_body( $label, $data );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers );
    }

    /**
     * Build the HTML email body.
     */
    private static function build_body( $event_label, $data ) {
        $site_name = esc_html( get_bloginfo( 'name' ) );
        $site_url  = esc_url( get_site_url() );
        $time      = esc_html( current_time( 'Y-m-d H:i:s' ) );

        $rows = '';
        foreach ( $data as $key => $value ) {
            $rows .= sprintf(
                '<tr><td style="padding:6px 12px;font-weight:bold;background:#f0f0f1;">%s</td><td style="padding:6px 12px;">%s</td></tr>',
                esc_html( ucwords( str_replace( '_', ' ', $key ) ) ),
                esc_html( (string) $value )
            );
        }

        return sprintf(
            '<!DOCTYPE html><html><body style="font-family:sans-serif;font-size:14px;color:#1d2327;">
            <div style="max-width:600px;margin:30px auto;border:1px solid #c3c4c7;border-radius:8px;overflow:hidden;">
                <div style="background:#1c1c28;padding:20px 24px;">
                    <h2 style="margin:0;color:#fff;font-size:18px;">AI1WM Manager</h2>
                    <p style="margin:4px 0 0;color:rgba(255,255,255,.6);font-size:13px;">%s</p>
                </div>
                <div style="padding:24px;">
                    <h3 style="margin-top:0;color:#2271b1;">%s</h3>
                    <table style="width:100%%;border-collapse:collapse;margin:16px 0;">%s</table>
                    <p style="color:#646970;font-size:12px;margin-top:24px;">
                        Sent from <a href="%s" style="color:#2271b1;">%s</a> at %s
                    </p>
                </div>
            </div>
            </body></html>',
            $site_name,
            esc_html( $event_label ),
            $rows,
            $site_url,
            $site_name,
            $time
        );
    }
}
