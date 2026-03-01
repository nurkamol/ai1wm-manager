<?php
/**
 * Extensions tab — search, grid of extension cards with version inputs, backups.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$file_exists   = $ext->extensions_file_exists();
$current_vers  = $ext->get_current_versions();
$installed     = $ext->get_installation_status();
$names         = $ext->get_extension_names();
$defaults      = $ext->get_extension_versions();
$ext_backups   = AI1WM_Manager_Backup_Manager::get_all( 'extension' );
$latest_backup = ! empty( $ext_backups ) ? $ext_backups[0] : null;
?>

<div class="ai1wm-page-header">
    <h1><?php esc_html_e( 'Extensions', 'ai1wm-manager' ); ?></h1>
    <p class="ai1wm-page-desc"><?php esc_html_e( 'Manage All-in-One WP Migration extension versions.', 'ai1wm-manager' ); ?></p>
</div>

<?php if ( ! $file_exists ) : ?>
<div class="ai1wm-alert ai1wm-alert-error">
    <span class="dashicons dashicons-warning"></span>
    <?php
    printf(
        esc_html__( 'Extensions file not found at %s. Please install the All-in-One WP Migration plugin.', 'ai1wm-manager' ),
        '<code>' . esc_html( $ext->get_file_path() ) . '</code>'
    );
    ?>
</div>
<?php else : ?>

<!-- Toolbar -->
<div class="ai1wm-toolbar">
    <div class="ai1wm-toolbar-left">
        <input type="text" id="ai1wm-ext-search" class="ai1wm-search-input" placeholder="<?php esc_attr_e( 'Search extensions…', 'ai1wm-manager' ); ?>">
    </div>
    <div class="ai1wm-toolbar-right">
        <button type="button" class="ai1wm-btn ai1wm-btn-secondary" id="ai1wm-select-all-ext">
            <?php esc_html_e( 'Select All', 'ai1wm-manager' ); ?>
        </button>
        <button type="button" class="ai1wm-btn ai1wm-btn-primary" id="ai1wm-backup-ext-btn"
                data-action="backupExtensions">
            <span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Backup Versions', 'ai1wm-manager' ); ?>
        </button>
        <button type="button" class="ai1wm-btn ai1wm-btn-primary" id="ai1wm-update-ext-btn"
                data-action="updateExtensions">
            <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Update Selected', 'ai1wm-manager' ); ?>
        </button>
    </div>
</div>

<?php if ( $latest_backup ) : ?>
<div class="ai1wm-alert ai1wm-alert-info">
    <span class="dashicons dashicons-backup"></span>
    <?php
    printf(
        esc_html__( 'Last backup: %s — ', 'ai1wm-manager' ),
        esc_html( date_i18n( 'M j, Y H:i', $latest_backup['timestamp'] ) )
    );
    ?>
    <button type="button" class="ai1wm-link-btn" data-action="revertExtensions"
            data-key="<?php echo esc_attr( $latest_backup['option_name'] ); ?>">
        <?php esc_html_e( 'Revert to this backup', 'ai1wm-manager' ); ?>
    </button>
</div>
<?php endif; ?>

<!-- Extension Cards Grid -->
<div class="ai1wm-extensions-grid" id="ai1wm-extensions-grid">
    <?php foreach ( $names as $prefix => $name ) :
        $current_v   = $current_vers[ $prefix ] ?? null;
        $default_v   = $defaults[ $prefix ] ?? '';
        $is_installed = $installed[ $prefix ] ?? false;
    ?>
    <div class="ai1wm-ext-card <?php echo ! $is_installed ? 'ai1wm-ext-card--not-installed' : ''; ?>"
         data-name="<?php echo esc_attr( strtolower( $name ) ); ?>">
        <div class="ai1wm-ext-card-header">
            <label class="ai1wm-ext-checkbox-label">
                <input type="checkbox" class="ai1wm-ext-checkbox" data-prefix="<?php echo esc_attr( $prefix ); ?>">
                <span class="ai1wm-ext-name"><?php echo esc_html( $name ); ?></span>
            </label>
            <div class="ai1wm-ext-status">
                <?php if ( $is_installed ) : ?>
                    <span class="ai1wm-badge ai1wm-badge-green" title="<?php esc_attr_e( 'Plugin folder found', 'ai1wm-manager' ); ?>">
                        <span class="dashicons dashicons-yes"></span>
                    </span>
                <?php else : ?>
                    <span class="ai1wm-badge ai1wm-badge-gray" title="<?php esc_attr_e( 'Plugin folder not found — version update will still work', 'ai1wm-manager' ); ?>">
                        <span class="dashicons dashicons-minus"></span>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="ai1wm-ext-card-body">
            <div class="ai1wm-ext-meta">
                <span class="ai1wm-label"><?php esc_html_e( 'Prefix:', 'ai1wm-manager' ); ?></span>
                <code><?php echo esc_html( $prefix ); ?></code>
            </div>
            <div class="ai1wm-ext-meta">
                <span class="ai1wm-label"><?php esc_html_e( 'Current:', 'ai1wm-manager' ); ?></span>
                <?php if ( $current_v !== null ) : ?>
                    <strong><?php echo esc_html( $current_v ); ?></strong>
                <?php else : ?>
                    <span class="ai1wm-text-muted"><?php esc_html_e( 'Not in file', 'ai1wm-manager' ); ?></span>
                <?php endif; ?>
            </div>
            <div class="ai1wm-ext-version-row">
                <label class="ai1wm-label"><?php esc_html_e( 'New version:', 'ai1wm-manager' ); ?></label>
                <input type="text"
                       class="ai1wm-ext-version-input ai1wm-input-sm"
                       data-prefix="<?php echo esc_attr( $prefix ); ?>"
                       value="<?php echo esc_attr( $current_v ?? $default_v ); ?>"
                       placeholder="e.g. 2.84"
                       pattern="[0-9]+(\.[0-9]+)*">
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="ai1wm-ext-no-results" class="ai1wm-empty-state" style="display:none;">
    <span class="dashicons dashicons-search" style="font-size:3em;color:#c3c4c7;"></span>
    <p><?php esc_html_e( 'No extensions match your search.', 'ai1wm-manager' ); ?></p>
</div>

<?php endif; ?>

<!-- Extension Backups -->
<?php if ( ! empty( $ext_backups ) ) : ?>
<div class="ai1wm-card" style="margin-top:30px;">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Extension Backups', 'ai1wm-manager' ); ?></h2>
        <button type="button" class="ai1wm-btn ai1wm-btn-danger ai1wm-btn-sm"
                data-action="removeAllBackups" data-type="extension">
            <?php esc_html_e( 'Remove All', 'ai1wm-manager' ); ?>
        </button>
    </div>
    <div class="ai1wm-card-body">
        <div class="ai1wm-backup-list">
            <?php foreach ( $ext_backups as $backup ) : ?>
            <div class="ai1wm-backup-item" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>">
                <div class="ai1wm-backup-icon">
                    <span class="dashicons dashicons-admin-plugins"></span>
                </div>
                <div class="ai1wm-backup-meta">
                    <div class="ai1wm-backup-title">
                        <?php echo esc_html( date_i18n( 'M j, Y H:i:s', $backup['timestamp'] ) ); ?>
                        <span class="ai1wm-badge ai1wm-badge-blue" style="margin-left:8px;"><?php echo esc_html( $backup['versions_count'] ?? 0 ); ?> <?php esc_html_e( 'versions', 'ai1wm-manager' ); ?></span>
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
                            data-action="revertExtensions" data-key="<?php echo esc_attr( $backup['option_name'] ); ?>">
                        <span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Revert', 'ai1wm-manager' ); ?>
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
