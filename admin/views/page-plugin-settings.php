<?php
/**
 * Plugin Settings tab — backup limit, auto-backup schedule, email notifications, log retention.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$options  = get_option( 'ai1wm_manager_options', AI1WM_Manager_Installer::default_options() );
$next_run = AI1WM_Manager_Scheduler::get_next_run();
$events   = AI1WM_Manager_Notifications::EVENTS;
?>

<div class="ai1wm-page-header">
    <h1><?php esc_html_e( 'Plugin Options', 'ai1wm-manager' ); ?></h1>
    <p class="ai1wm-page-desc"><?php esc_html_e( 'Configure backup limits, scheduled backups, and email notifications.', 'ai1wm-manager' ); ?></p>
</div>

<form id="ai1wm-options-form">

<!-- Backup Settings -->
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Backup Settings', 'ai1wm-manager' ); ?></h2>
    </div>
    <div class="ai1wm-card-body">
        <div class="ai1wm-form-row">
            <div class="ai1wm-form-label">
                <label for="ai1wm-backup-limit"><?php esc_html_e( 'Max Backups per Type', 'ai1wm-manager' ); ?></label>
                <p class="ai1wm-field-desc"><?php esc_html_e( 'Older backups are automatically removed when this limit is exceeded.', 'ai1wm-manager' ); ?></p>
            </div>
            <div class="ai1wm-form-control">
                <input type="number" id="ai1wm-backup-limit" name="options[backup_limit]"
                       class="ai1wm-input ai1wm-input-sm" min="1" max="50"
                       value="<?php echo esc_attr( $options['backup_limit'] ?? 5 ); ?>">
                <span class="ai1wm-field-suffix"><?php esc_html_e( 'backups', 'ai1wm-manager' ); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Backup Schedule -->
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Scheduled Auto-Backup', 'ai1wm-manager' ); ?></h2>
    </div>
    <div class="ai1wm-card-body">
        <div class="ai1wm-form-row">
            <div class="ai1wm-form-label">
                <label><?php esc_html_e( 'Schedule', 'ai1wm-manager' ); ?></label>
                <p class="ai1wm-field-desc"><?php esc_html_e( 'Automatically create a settings backup on the chosen interval.', 'ai1wm-manager' ); ?></p>
            </div>
            <div class="ai1wm-form-control">
                <div class="ai1wm-radio-group">
                    <?php foreach ( array( 'disabled' => __( 'Disabled', 'ai1wm-manager' ), 'daily' => __( 'Daily', 'ai1wm-manager' ), 'weekly' => __( 'Weekly', 'ai1wm-manager' ), 'monthly' => __( 'Monthly', 'ai1wm-manager' ) ) as $val => $label ) : ?>
                    <label class="ai1wm-radio">
                        <input type="radio" name="options[auto_backup_schedule]"
                               value="<?php echo esc_attr( $val ); ?>"
                               <?php checked( $options['auto_backup_schedule'] ?? 'disabled', $val ); ?>>
                        <span><?php echo esc_html( $label ); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php if ( ( $options['auto_backup_schedule'] ?? 'disabled' ) !== 'disabled' ) : ?>
                <p class="ai1wm-field-desc" style="margin-top:8px;">
                    <span class="dashicons dashicons-clock" style="font-size:14px;line-height:1.6;"></span>
                    <?php echo esc_html( $next_run ); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Email Notifications -->
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Email Notifications', 'ai1wm-manager' ); ?></h2>
    </div>
    <div class="ai1wm-card-body">
        <div class="ai1wm-form-row">
            <div class="ai1wm-form-label">
                <label><?php esc_html_e( 'Enable Notifications', 'ai1wm-manager' ); ?></label>
            </div>
            <div class="ai1wm-form-control">
                <label class="ai1wm-toggle">
                    <input type="checkbox" name="options[notifications_enabled]" value="1" id="ai1wm-notif-toggle"
                           <?php checked( ! empty( $options['notifications_enabled'] ) ); ?>>
                    <span class="ai1wm-toggle-slider"></span>
                    <span><?php esc_html_e( 'Send email notifications', 'ai1wm-manager' ); ?></span>
                </label>
            </div>
        </div>

        <div id="ai1wm-notif-details" <?php echo empty( $options['notifications_enabled'] ) ? 'style="display:none;"' : ''; ?>>
            <div class="ai1wm-form-row">
                <div class="ai1wm-form-label">
                    <label for="ai1wm-notif-email"><?php esc_html_e( 'Recipient Email', 'ai1wm-manager' ); ?></label>
                </div>
                <div class="ai1wm-form-control">
                    <input type="email" id="ai1wm-notif-email" name="options[notification_email]"
                           class="ai1wm-input" value="<?php echo esc_attr( $options['notification_email'] ?? get_option( 'admin_email' ) ); ?>">
                </div>
            </div>

            <div class="ai1wm-form-row">
                <div class="ai1wm-form-label">
                    <label><?php esc_html_e( 'Notify on', 'ai1wm-manager' ); ?></label>
                </div>
                <div class="ai1wm-form-control">
                    <?php
                    $active_events = (array) ( $options['notification_events'] ?? array( 'backup_created', 'import_complete', 'backup_failed' ) );
                    foreach ( $events as $event_key => $event_label ) : ?>
                    <label class="ai1wm-checkbox">
                        <input type="checkbox" name="options[notification_events][]"
                               value="<?php echo esc_attr( $event_key ); ?>"
                               <?php checked( in_array( $event_key, $active_events, true ) ); ?>>
                        <span><?php echo esc_html( $event_label ); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Log Retention -->
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Activity Log', 'ai1wm-manager' ); ?></h2>
    </div>
    <div class="ai1wm-card-body">
        <div class="ai1wm-form-row">
            <div class="ai1wm-form-label">
                <label><?php esc_html_e( 'Log Retention', 'ai1wm-manager' ); ?></label>
                <p class="ai1wm-field-desc"><?php esc_html_e( 'Entries older than this will be automatically removed.', 'ai1wm-manager' ); ?></p>
            </div>
            <div class="ai1wm-form-control">
                <select name="options[log_retention_days]" class="ai1wm-select">
                    <?php foreach ( array( 30 => __( '30 days', 'ai1wm-manager' ), 60 => __( '60 days', 'ai1wm-manager' ), 90 => __( '90 days', 'ai1wm-manager' ), 0 => __( 'Keep forever', 'ai1wm-manager' ) ) as $days => $label ) : ?>
                    <option value="<?php echo esc_attr( $days ); ?>" <?php selected( (int) ( $options['log_retention_days'] ?? 60 ), $days ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="ai1wm-form-submit">
    <button type="button" class="ai1wm-btn ai1wm-btn-primary ai1wm-btn-lg" data-action="saveOptions">
        <span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Settings', 'ai1wm-manager' ); ?>
    </button>
</div>

</form>

<script>
document.getElementById('ai1wm-notif-toggle').addEventListener('change', function() {
    document.getElementById('ai1wm-notif-details').style.display = this.checked ? '' : 'none';
});
</script>
