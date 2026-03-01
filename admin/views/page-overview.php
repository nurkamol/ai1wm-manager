<?php
/**
 * Overview tab — system status, quick stats, recent activity, backup list, changelog.
 * Available variables: $ext (AI1WM_Manager_Extensions_Manager), $settings (AI1WM_Manager_Settings_Manager)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$file_exists     = $ext->extensions_file_exists();
$current_vers    = $ext->get_current_versions();
$installed       = $ext->get_installation_status();
$installed_count = count( array_filter( $installed ) );
$all_settings    = $settings->get_settings( false );
$all_backups     = AI1WM_Manager_Backup_Manager::get_all( 'all' );
$latest_backup   = ! empty( $all_backups ) ? $all_backups[0] : null;
$options         = get_option( 'ai1wm_manager_options', AI1WM_Manager_Installer::default_options() );
$log_result      = AI1WM_Manager_Activity_Log::get_entries( array( 'per_page' => 5, 'page' => 1 ) );

$changelog = array(
    '4.0.0' => array(
        'date' => '2026-03-01', 'type' => 'major',
        'changes' => array(
            'Added'   => array( 'Modular multi-file architecture', 'AJAX-powered operations (no page reloads)', 'Activity / Audit Log with DB storage', 'WP-Cron scheduled auto-backups', 'Email notifications for key events', 'Diff view and selective restore for settings', 'Dry-run import preview', 'WP Dashboard widget', 'WP-CLI commands (backup, export, list)', 'Backup notes / labels', 'Extension compatibility check', 'Configurable backup limit', 'Individual backup download', 'Search & filter for extensions', 'i18n / translation support' ),
            'Changed' => array( 'Complete UI redesign with dark sidebar', 'Migrated from single PHP file to MVC structure', 'PHP 7.4+ and WordPress 5.6+ requirement' ),
        ),
    ),
    '3.1.1' => array(
        'date' => '2025-07-26', 'type' => 'hotfix',
        'changes' => array(
            'Fixed' => array( 'WordPress Core Conflict: Resolved "Security check failed" error', 'Action Handler Isolation to avoid interfering with WP core' ),
        ),
    ),
);
?>

<div class="ai1wm-page-header">
    <h1><?php esc_html_e( 'Overview', 'ai1wm-manager' ); ?></h1>
    <p class="ai1wm-page-desc"><?php esc_html_e( 'System status, recent activity, and backup management.', 'ai1wm-manager' ); ?></p>
</div>

<!-- Quick Stats -->
<div class="ai1wm-stats-grid">
    <div class="ai1wm-stat-card">
        <div class="ai1wm-stat-value"><?php echo esc_html( count( $all_backups ) ); ?></div>
        <div class="ai1wm-stat-label"><?php esc_html_e( 'Total Backups', 'ai1wm-manager' ); ?></div>
    </div>
    <div class="ai1wm-stat-card">
        <div class="ai1wm-stat-value"><?php echo esc_html( count( $all_settings ) ); ?></div>
        <div class="ai1wm-stat-label"><?php esc_html_e( 'AI1WM Settings', 'ai1wm-manager' ); ?></div>
    </div>
    <div class="ai1wm-stat-card">
        <div class="ai1wm-stat-value"><?php echo esc_html( $installed_count ); ?> / <?php echo esc_html( count( $installed ) ); ?></div>
        <div class="ai1wm-stat-label"><?php esc_html_e( 'Extensions Installed', 'ai1wm-manager' ); ?></div>
    </div>
    <div class="ai1wm-stat-card">
        <div class="ai1wm-stat-value ai1wm-stat-small">
            <?php echo $latest_backup ? esc_html( date_i18n( 'M j', $latest_backup['timestamp'] ) ) : '—'; ?>
        </div>
        <div class="ai1wm-stat-label"><?php esc_html_e( 'Last Backup', 'ai1wm-manager' ); ?></div>
    </div>
</div>

<!-- System Status -->
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'System Status', 'ai1wm-manager' ); ?></h2>
    </div>
    <div class="ai1wm-card-body">
        <table class="ai1wm-status-table">
            <tbody>
                <tr>
                    <td><?php esc_html_e( 'Plugin Version', 'ai1wm-manager' ); ?></td>
                    <td><span class="ai1wm-badge ai1wm-badge-blue"><?php echo esc_html( AI1WM_MANAGER_VERSION ); ?></span></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'WordPress Version', 'ai1wm-manager' ); ?></td>
                    <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'PHP Version', 'ai1wm-manager' ); ?></td>
                    <td><?php echo esc_html( PHP_VERSION ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'AI1WM Extensions File', 'ai1wm-manager' ); ?></td>
                    <td>
                        <?php if ( $file_exists ) : ?>
                            <span class="ai1wm-badge ai1wm-badge-green"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Found', 'ai1wm-manager' ); ?></span>
                        <?php else : ?>
                            <span class="ai1wm-badge ai1wm-badge-red"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Not Found — install AI1WM plugin', 'ai1wm-manager' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Auto-Backup Schedule', 'ai1wm-manager' ); ?></td>
                    <td>
                        <?php
                        $schedule = $options['auto_backup_schedule'] ?? 'disabled';
                        $badge    = $schedule === 'disabled' ? 'ai1wm-badge-gray' : 'ai1wm-badge-green';
                        ?>
                        <span class="ai1wm-badge <?php echo esc_attr( $badge ); ?>"><?php echo esc_html( ucfirst( $schedule ) ); ?></span>
                        <?php if ( $schedule !== 'disabled' ) : ?>
                            <span class="ai1wm-text-muted"> — <?php echo esc_html( AI1WM_Manager_Scheduler::get_next_run() ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Backup Limit', 'ai1wm-manager' ); ?></td>
                    <td><?php echo esc_html( $options['backup_limit'] ?? 5 ); ?> <?php esc_html_e( 'per type', 'ai1wm-manager' ); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Activity -->
<?php if ( ! empty( $log_result['items'] ) ) : ?>
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Recent Activity', 'ai1wm-manager' ); ?></h2>
        <a href="<?php echo esc_url( add_query_arg( array( 'page' => AI1WM_MANAGER_SLUG, 'tab' => 'activity-log' ), admin_url( 'tools.php' ) ) ); ?>" class="ai1wm-link">
            <?php esc_html_e( 'View all', 'ai1wm-manager' ); ?> &rarr;
        </a>
    </div>
    <div class="ai1wm-card-body ai1wm-card-body--flush">
        <table class="ai1wm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Date', 'ai1wm-manager' ); ?></th>
                    <th><?php esc_html_e( 'User', 'ai1wm-manager' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'ai1wm-manager' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'ai1wm-manager' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $log_result['items'] as $entry ) : ?>
                <tr>
                    <td class="ai1wm-text-muted ai1wm-nowrap"><?php echo esc_html( $entry['created_at'] ); ?></td>
                    <td><?php echo esc_html( $entry['user_login'] ); ?></td>
                    <td><span class="ai1wm-action-badge"><?php echo esc_html( str_replace( '_', ' ', $entry['action'] ) ); ?></span></td>
                    <td><?php echo esc_html( $entry['description'] ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Backup Management -->
<?php if ( ! empty( $all_backups ) ) : ?>
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Backup Management', 'ai1wm-manager' ); ?></h2>
        <button type="button" class="ai1wm-btn ai1wm-btn-danger ai1wm-btn-sm"
                data-action="removeAllBackups" data-type="all">
            <?php esc_html_e( 'Remove All', 'ai1wm-manager' ); ?>
        </button>
    </div>
    <div class="ai1wm-card-body">
        <div class="ai1wm-backup-list">
            <?php foreach ( $all_backups as $backup ) : ?>
            <div class="ai1wm-backup-item" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>">
                <div class="ai1wm-backup-icon">
                    <span class="dashicons <?php echo $backup['type'] === 'settings' ? 'dashicons-admin-settings' : 'dashicons-admin-plugins'; ?>"></span>
                </div>
                <div class="ai1wm-backup-meta">
                    <div class="ai1wm-backup-title">
                        <?php echo esc_html( ucfirst( $backup['type'] ?? 'Backup' ) ); ?>
                        <span class="ai1wm-text-muted"> — <?php echo esc_html( date_i18n( 'M j, Y H:i', $backup['timestamp'] ) ); ?></span>
                    </div>
                    <?php if ( ! empty( $backup['note'] ) ) : ?>
                        <div class="ai1wm-backup-note"><?php echo esc_html( $backup['note'] ); ?></div>
                    <?php endif; ?>
                </div>
                <div class="ai1wm-backup-actions">
                    <button type="button" class="ai1wm-btn ai1wm-btn-ghost ai1wm-btn-sm"
                            data-action="editNote" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>"
                            data-note="<?php echo esc_attr( $backup['note'] ?? '' ); ?>"
                            title="<?php esc_attr_e( 'Edit note', 'ai1wm-manager' ); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="ai1wm-btn ai1wm-btn-ghost ai1wm-btn-sm"
                            data-action="downloadBackup" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>"
                            title="<?php esc_attr_e( 'Download', 'ai1wm-manager' ); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                    <?php if ( $backup['type'] === 'settings' ) : ?>
                    <button type="button" class="ai1wm-btn ai1wm-btn-secondary ai1wm-btn-sm"
                            data-action="restoreSettings" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>"
                            title="<?php esc_attr_e( 'Restore settings from this backup', 'ai1wm-manager' ); ?>">
                        <span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Restore', 'ai1wm-manager' ); ?>
                    </button>
                    <?php else : ?>
                    <button type="button" class="ai1wm-btn ai1wm-btn-secondary ai1wm-btn-sm"
                            data-action="revertExtensions" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>"
                            title="<?php esc_attr_e( 'Revert extensions to this backup', 'ai1wm-manager' ); ?>">
                        <span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Revert', 'ai1wm-manager' ); ?>
                    </button>
                    <?php endif; ?>
                    <button type="button" class="ai1wm-btn ai1wm-btn-danger ai1wm-btn-sm"
                            data-action="removeBackup" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Changelog -->
<div class="ai1wm-card ai1wm-card--collapsible">
    <div class="ai1wm-card-header ai1wm-card-header--clickable" onclick="AI1WM.toggleCard(this)">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'Changelog', 'ai1wm-manager' ); ?></h2>
        <span class="ai1wm-toggle-icon dashicons dashicons-arrow-down-alt2"></span>
    </div>
    <div class="ai1wm-card-body" style="display:none;">
        <?php foreach ( $changelog as $version => $info ) : ?>
        <div class="ai1wm-changelog-entry">
            <div class="ai1wm-changelog-version-row">
                <span class="ai1wm-version-badge">v<?php echo esc_html( $version ); ?></span>
                <span class="ai1wm-text-muted"><?php echo esc_html( $info['date'] ); ?></span>
                <span class="ai1wm-release-type ai1wm-release-<?php echo esc_attr( $info['type'] ); ?>"><?php echo esc_html( $info['type'] ); ?></span>
            </div>
            <?php foreach ( $info['changes'] as $category => $items ) : ?>
            <div class="ai1wm-changelog-section">
                <h4><?php echo esc_html( $category ); ?></h4>
                <ul>
                    <?php foreach ( $items as $item ) : ?>
                        <li><?php echo esc_html( $item ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
