<?php
/**
 * WordPress Dashboard widget showing a quick status summary.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Dashboard_Widget {

    /**
     * Register the dashboard widget.
     */
    public function register_hooks() {
        add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
    }

    public function register() {
        wp_add_dashboard_widget(
            'ai1wm_manager_widget',
            __( 'AI1WM Manager Status', 'ai1wm-manager' ),
            array( $this, 'render' )
        );
    }

    public function render() {
        $ext_file_exists = file_exists( WP_CONTENT_DIR . '/plugins/all-in-one-wp-migration/lib/model/class-ai1wm-extensions.php' );
        $all_backups     = AI1WM_Manager_Backup_Manager::get_all( 'all' );
        $latest_backup   = ! empty( $all_backups ) ? $all_backups[0] : null;
        $manage_url      = admin_url( 'tools.php?page=' . AI1WM_MANAGER_SLUG );
        $options         = get_option( 'ai1wm_manager_options', array() );
        $schedule        = $options['auto_backup_schedule'] ?? 'disabled';

        ?>
        <div style="font-size:13px;line-height:1.7;">
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="padding:4px 0;color:#646970;width:55%;"><?php esc_html_e( 'Plugin Version', 'ai1wm-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:600;"><?php echo esc_html( AI1WM_MANAGER_VERSION ); ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#646970;"><?php esc_html_e( 'AI1WM Extensions File', 'ai1wm-manager' ); ?></td>
                    <td style="padding:4px 0;">
                        <?php if ( $ext_file_exists ) : ?>
                            <span style="color:#00a32a;font-weight:600;">&#10003; <?php esc_html_e( 'Found', 'ai1wm-manager' ); ?></span>
                        <?php else : ?>
                            <span style="color:#d63638;font-weight:600;">&#10007; <?php esc_html_e( 'Not Found', 'ai1wm-manager' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#646970;"><?php esc_html_e( 'Total Backups', 'ai1wm-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:600;"><?php echo esc_html( count( $all_backups ) ); ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#646970;"><?php esc_html_e( 'Last Backup', 'ai1wm-manager' ); ?></td>
                    <td style="padding:4px 0;">
                        <?php if ( $latest_backup ) : ?>
                            <?php echo esc_html( date_i18n( 'M j, Y H:i', $latest_backup['timestamp'] ) ); ?>
                        <?php else : ?>
                            <em style="color:#646970;"><?php esc_html_e( 'None', 'ai1wm-manager' ); ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#646970;"><?php esc_html_e( 'Auto-Backup', 'ai1wm-manager' ); ?></td>
                    <td style="padding:4px 0;font-weight:600;text-transform:capitalize;"><?php echo esc_html( $schedule ); ?></td>
                </tr>
            </table>

            <div style="margin-top:12px;padding-top:10px;border-top:1px solid #f0f0f1;">
                <a href="<?php echo esc_url( $manage_url ); ?>" class="button button-primary" style="width:100%;text-align:center;box-sizing:border-box;">
                    <?php esc_html_e( 'Open AI1WM Manager', 'ai1wm-manager' ); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
