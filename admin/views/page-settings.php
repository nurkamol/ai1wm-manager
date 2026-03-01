<?php
/**
 * Settings tab — export, import (dry-run + real), settings list, backups.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$all_settings    = $settings->get_settings( false );
$total_settings  = count( $all_settings );
$settings_backups = AI1WM_Manager_Backup_Manager::get_all( 'settings' );
?>

<div class="ai1wm-page-header">
    <h1><?php esc_html_e( 'Settings Manager', 'ai1wm-manager' ); ?></h1>
    <p class="ai1wm-page-desc"><?php esc_html_e( 'Export, import, and restore AI1WM plugin settings.', 'ai1wm-manager' ); ?></p>
</div>

<div class="ai1wm-two-col">

    <!-- Export Panel -->
    <div class="ai1wm-card">
        <div class="ai1wm-card-header">
            <h2 class="ai1wm-card-title"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Settings', 'ai1wm-manager' ); ?></h2>
        </div>
        <div class="ai1wm-card-body">
            <p class="ai1wm-text-muted">
                <?php esc_html_e( 'Download your current AI1WM settings as a JSON file. Site-specific values (paths, keys, timestamps) are automatically excluded.', 'ai1wm-manager' ); ?>
            </p>
            <div class="ai1wm-field">
                <label class="ai1wm-toggle">
                    <input type="checkbox" id="ai1wm-export-redact">
                    <span class="ai1wm-toggle-slider"></span>
                    <span><?php esc_html_e( 'Redact sensitive values', 'ai1wm-manager' ); ?></span>
                </label>
            </div>
            <div class="ai1wm-field">
                <label class="ai1wm-toggle">
                    <input type="checkbox" id="ai1wm-export-metadata" checked>
                    <span class="ai1wm-toggle-slider"></span>
                    <span><?php esc_html_e( 'Include export metadata', 'ai1wm-manager' ); ?></span>
                </label>
            </div>
            <div class="ai1wm-field-group">
                <button type="button" class="ai1wm-btn ai1wm-btn-primary" data-action="exportSettings">
                    <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Settings', 'ai1wm-manager' ); ?>
                </button>
                <button type="button" class="ai1wm-btn ai1wm-btn-secondary" data-action="backupSettings">
                    <span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Create DB Backup', 'ai1wm-manager' ); ?>
                </button>
            </div>
            <p class="ai1wm-text-muted" style="margin-top:8px;">
                <?php printf( esc_html__( '%d settings available for export.', 'ai1wm-manager' ), $total_settings ); ?>
            </p>
        </div>
    </div>

    <!-- Import Panel -->
    <div class="ai1wm-card">
        <div class="ai1wm-card-header">
            <h2 class="ai1wm-card-title"><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import Settings', 'ai1wm-manager' ); ?></h2>
        </div>
        <div class="ai1wm-card-body">
            <div class="ai1wm-alert ai1wm-alert-warning">
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e( 'A backup is automatically created before every import. Use Dry-Run Preview first to see what will change.', 'ai1wm-manager' ); ?>
            </div>

            <div class="ai1wm-file-drop" id="ai1wm-import-drop">
                <input type="file" id="ai1wm-import-file" accept=".json" style="display:none;">
                <div class="ai1wm-file-drop-inner" onclick="document.getElementById('ai1wm-import-file').click()">
                    <span class="dashicons dashicons-upload ai1wm-drop-icon"></span>
                    <p><?php esc_html_e( 'Click or drag & drop your JSON file here', 'ai1wm-manager' ); ?></p>
                    <p class="ai1wm-text-muted"><?php esc_html_e( 'Maximum file size: 10 MB', 'ai1wm-manager' ); ?></p>
                </div>
                <div id="ai1wm-import-file-name" class="ai1wm-file-name" style="display:none;"></div>
            </div>

            <div class="ai1wm-field-group" style="margin-top:16px;">
                <button type="button" class="ai1wm-btn ai1wm-btn-secondary" id="ai1wm-dry-run-btn" disabled>
                    <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Preview Changes', 'ai1wm-manager' ); ?>
                </button>
                <button type="button" class="ai1wm-btn ai1wm-btn-primary" id="ai1wm-import-btn" disabled>
                    <span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import Now', 'ai1wm-manager' ); ?>
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Current Settings Viewer -->
<?php if ( $total_settings > 0 ) : ?>
<div class="ai1wm-card">
    <div class="ai1wm-card-header ai1wm-card-header--clickable" onclick="AI1WM.toggleCard(this)">
        <h2 class="ai1wm-card-title">
            <span class="dashicons dashicons-database"></span>
            <?php printf( esc_html__( 'Current Settings (%d)', 'ai1wm-manager' ), $total_settings ); ?>
        </h2>
        <span class="ai1wm-toggle-icon dashicons dashicons-arrow-down-alt2"></span>
    </div>
    <div class="ai1wm-card-body" style="display:none;">
        <div class="ai1wm-settings-search-wrap">
            <input type="text" id="ai1wm-settings-search" class="ai1wm-search-input"
                   placeholder="<?php esc_attr_e( 'Filter settings by key…', 'ai1wm-manager' ); ?>">
        </div>
        <div class="ai1wm-settings-list" id="ai1wm-settings-list">
            <?php foreach ( $all_settings as $key => $value ) :
                $display = is_array( $value ) || is_object( $value )
                    ? wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
                    : (string) $value;
            ?>
            <div class="ai1wm-setting-row" data-key="<?php echo esc_attr( strtolower( $key ) ); ?>">
                <div class="ai1wm-setting-key"><code><?php echo esc_html( $key ); ?></code></div>
                <div class="ai1wm-setting-value"><pre><?php echo esc_html( mb_strimwidth( $display, 0, 500, '…' ) ); ?></pre></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Settings Backups -->
<?php if ( ! empty( $settings_backups ) ) : ?>
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Settings Backups', 'ai1wm-manager' ); ?></h2>
        <button type="button" class="ai1wm-btn ai1wm-btn-danger ai1wm-btn-sm"
                data-action="removeAllBackups" data-type="settings">
            <?php esc_html_e( 'Remove All', 'ai1wm-manager' ); ?>
        </button>
    </div>
    <div class="ai1wm-card-body">
        <div class="ai1wm-backup-list">
            <?php foreach ( $settings_backups as $backup ) : ?>
            <div class="ai1wm-backup-item" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>">
                <div class="ai1wm-backup-icon">
                    <span class="dashicons dashicons-admin-settings"></span>
                </div>
                <div class="ai1wm-backup-meta">
                    <div class="ai1wm-backup-title">
                        <?php echo esc_html( date_i18n( 'M j, Y H:i:s', $backup['timestamp'] ) ); ?>
                        <?php if ( ! empty( $backup['count'] ) ) : ?>
                        <span class="ai1wm-badge ai1wm-badge-blue" style="margin-left:8px;"><?php echo esc_html( $backup['count'] ); ?> <?php esc_html_e( 'settings', 'ai1wm-manager' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( ! empty( $backup['note'] ) ) : ?>
                        <div class="ai1wm-backup-note"><?php echo esc_html( $backup['note'] ); ?></div>
                    <?php endif; ?>
                </div>
                <div class="ai1wm-backup-actions">
                    <button type="button" class="ai1wm-btn ai1wm-btn-ghost ai1wm-btn-sm"
                            data-action="editNote" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>"
                            data-note="<?php echo esc_attr( $backup['note'] ?? '' ); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="ai1wm-btn ai1wm-btn-ghost ai1wm-btn-sm"
                            data-action="downloadBackup" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                    <button type="button" class="ai1wm-btn ai1wm-btn-secondary ai1wm-btn-sm"
                            data-action="restoreSettings" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>">
                        <span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Restore', 'ai1wm-manager' ); ?>
                    </button>
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
