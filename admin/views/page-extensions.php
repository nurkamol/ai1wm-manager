<?php
/**
 * Extensions tab — table + card views with toggle.
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
        <div class="ai1wm-view-toggle" id="ai1wm-view-toggle" role="group" aria-label="<?php esc_attr_e( 'View mode', 'ai1wm-manager' ); ?>">
            <button type="button" class="ai1wm-view-btn active" data-view="table" title="<?php esc_attr_e( 'Table view', 'ai1wm-manager' ); ?>">
                <span class="dashicons dashicons-editor-table"></span> <?php esc_html_e( 'Table', 'ai1wm-manager' ); ?>
            </button>
            <button type="button" class="ai1wm-view-btn" data-view="card" title="<?php esc_attr_e( 'Card view', 'ai1wm-manager' ); ?>">
                <span class="dashicons dashicons-grid-view"></span> <?php esc_html_e( 'Cards', 'ai1wm-manager' ); ?>
            </button>
        </div>
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

<!-- Bulk action bar — shown by JS when one or more extensions are selected -->
<div class="ai1wm-bulk-bar" id="ai1wm-bulk-bar" style="display:none;">
    <div class="ai1wm-bulk-bar-info">
        <span class="dashicons dashicons-yes"></span>
        <span id="ai1wm-bulk-count">0</span> <?php esc_html_e( 'selected', 'ai1wm-manager' ); ?>
    </div>
    <div class="ai1wm-bulk-bar-actions">
        <div class="ai1wm-bulk-apply">
            <input type="text" id="ai1wm-bulk-version" class="ai1wm-input ai1wm-input-sm"
                   placeholder="<?php esc_attr_e( 'e.g. 2.84', 'ai1wm-manager' ); ?>"
                   pattern="[0-9]+(\.[0-9]+)*">
            <button type="button" class="ai1wm-btn ai1wm-btn-secondary ai1wm-btn-sm" id="ai1wm-bulk-apply-version">
                <?php esc_html_e( 'Set version for selected', 'ai1wm-manager' ); ?>
            </button>
        </div>
        <button type="button" class="ai1wm-btn ai1wm-btn-ghost ai1wm-btn-sm" id="ai1wm-bulk-reset-defaults">
            <span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e( 'Reset selected to defaults', 'ai1wm-manager' ); ?>
        </button>
        <button type="button" class="ai1wm-btn ai1wm-btn-ghost ai1wm-btn-sm" data-action="saveProfile">
            <span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Save as profile', 'ai1wm-manager' ); ?>
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

<!-- View wrapper — JS sets data-view="table|card" -->
<div class="ai1wm-ext-view-wrap" id="ai1wm-ext-view-wrap" data-view="table">

    <!-- ── Table view ──────────────────────────────────────────── -->
    <div class="ai1wm-ext-view ai1wm-ext-view--table">
        <div class="ai1wm-card ai1wm-ext-table-card">
            <div class="ai1wm-card-body" style="padding:0;">
                <div class="ai1wm-ext-table-header">
                    <h2><?php esc_html_e( 'Step 2: Update Extension Versions', 'ai1wm-manager' ); ?></h2>
                    <p><?php esc_html_e( 'Check the extensions you want to update and enter the new version numbers.', 'ai1wm-manager' ); ?></p>
                </div>
                <table class="ai1wm-ext-table">
                    <thead>
                        <tr>
                            <th class="ai1wm-ext-col-check"><?php esc_html_e( 'Update', 'ai1wm-manager' ); ?></th>
                            <th class="ai1wm-ext-col-name"><?php esc_html_e( 'Extension', 'ai1wm-manager' ); ?></th>
                            <th class="ai1wm-ext-col-current"><?php esc_html_e( 'Current Version', 'ai1wm-manager' ); ?></th>
                            <th class="ai1wm-ext-col-new"><?php esc_html_e( 'New Version', 'ai1wm-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $names as $prefix => $name ) :
                            $current_v    = $current_vers[ $prefix ] ?? null;
                            $default_v    = $defaults[ $prefix ] ?? '';
                            $is_installed = $installed[ $prefix ] ?? false;
                        ?>
                        <tr class="ai1wm-ext-row <?php echo ! $is_installed ? 'ai1wm-ext-row--not-installed' : ''; ?>"
                            data-name="<?php echo esc_attr( strtolower( $name ) ); ?>"
                            data-prefix="<?php echo esc_attr( $prefix ); ?>">
                            <td class="ai1wm-ext-col-check">
                                <input type="checkbox" class="ai1wm-ext-checkbox" data-prefix="<?php echo esc_attr( $prefix ); ?>">
                            </td>
                            <td class="ai1wm-ext-col-name">
                                <?php echo esc_html( $name ); ?>
                                <?php if ( ! $is_installed ) : ?>
                                    <span class="ai1wm-ext-not-installed-badge" title="<?php esc_attr_e( 'Plugin folder not found — version update will still work', 'ai1wm-manager' ); ?>">
                                        <span class="dashicons dashicons-minus"></span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="ai1wm-ext-col-current">
                                <?php if ( $current_v !== null ) : ?>
                                    <?php echo esc_html( $current_v ); ?>
                                <?php else : ?>
                                    <span class="ai1wm-text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="ai1wm-ext-col-new">
                                <input type="text"
                                       class="ai1wm-ext-version-input"
                                       data-prefix="<?php echo esc_attr( $prefix ); ?>"
                                       data-default="<?php echo esc_attr( $default_v ); ?>"
                                       value="<?php echo esc_attr( $current_v ?? $default_v ); ?>"
                                       placeholder="e.g. 2.84"
                                       pattern="[0-9]+(\.[0-9]+)*">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Card view ───────────────────────────────────────────── -->
    <div class="ai1wm-ext-view ai1wm-ext-view--card">
        <div class="ai1wm-extensions-grid" id="ai1wm-extensions-grid">
            <?php foreach ( $names as $prefix => $name ) :
                $current_v    = $current_vers[ $prefix ] ?? null;
                $default_v    = $defaults[ $prefix ] ?? '';
                $is_installed = $installed[ $prefix ] ?? false;
            ?>
            <div class="ai1wm-ext-card <?php echo ! $is_installed ? 'ai1wm-ext-card--not-installed' : ''; ?>"
                 data-name="<?php echo esc_attr( strtolower( $name ) ); ?>"
                 data-prefix="<?php echo esc_attr( $prefix ); ?>">
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
                               data-default="<?php echo esc_attr( $default_v ); ?>"
                               value="<?php echo esc_attr( $current_v ?? $default_v ); ?>"
                               placeholder="e.g. 2.84"
                               pattern="[0-9]+(\.[0-9]+)*">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- No results (shared) -->
    <div id="ai1wm-ext-no-results" class="ai1wm-empty-state" style="display:none;">
        <span class="dashicons dashicons-search" style="font-size:3em;color:#c3c4c7;"></span>
        <p><?php esc_html_e( 'No extensions match your search.', 'ai1wm-manager' ); ?></p>
    </div>

</div><!-- /.ai1wm-ext-view-wrap -->

<!-- Version Profiles -->
<?php $profiles = AI1WM_Manager_Profiles_Manager::get_all(); ?>
<div class="ai1wm-card" style="margin-top:30px;" id="ai1wm-profiles-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Version Profiles', 'ai1wm-manager' ); ?></h2>
        <button type="button" class="ai1wm-btn ai1wm-btn-secondary ai1wm-btn-sm" data-action="saveProfile" data-source="current">
            <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Save current versions', 'ai1wm-manager' ); ?>
        </button>
    </div>
    <div class="ai1wm-card-body">
        <p class="ai1wm-field-desc" style="margin-top:0;">
            <?php esc_html_e( 'Save a named set of extension versions and re-apply it in one click — handy for rolling back to a known-good combination.', 'ai1wm-manager' ); ?>
        </p>
        <div class="ai1wm-profile-list" id="ai1wm-profile-list" <?php echo empty( $profiles ) ? 'style="display:none;"' : ''; ?>>
            <?php foreach ( $profiles as $profile ) : ?>
            <div class="ai1wm-profile-item" data-id="<?php echo esc_attr( $profile['id'] ); ?>">
                <div class="ai1wm-profile-icon">
                    <span class="dashicons dashicons-star-filled"></span>
                </div>
                <div class="ai1wm-profile-meta">
                    <div class="ai1wm-profile-name"><?php echo esc_html( $profile['name'] ); ?></div>
                    <div class="ai1wm-profile-sub">
                        <?php
                        printf(
                            esc_html( _n( '%1$d extension · saved %2$s', '%1$d extensions · saved %2$s', count( $profile['versions'] ), 'ai1wm-manager' ) ),
                            count( $profile['versions'] ),
                            esc_html( date_i18n( 'M j, Y', $profile['created_at'] ) )
                        );
                        ?>
                    </div>
                </div>
                <div class="ai1wm-profile-actions">
                    <button type="button" class="ai1wm-btn ai1wm-btn-primary ai1wm-btn-sm"
                            data-action="applyProfile" data-id="<?php echo esc_attr( $profile['id'] ); ?>"
                            data-name="<?php echo esc_attr( $profile['name'] ); ?>">
                        <span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Apply', 'ai1wm-manager' ); ?>
                    </button>
                    <button type="button" class="ai1wm-btn ai1wm-btn-danger ai1wm-btn-sm"
                            data-action="deleteProfile" data-id="<?php echo esc_attr( $profile['id'] ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="ai1wm-empty-state ai1wm-profile-empty" id="ai1wm-profile-empty" <?php echo empty( $profiles ) ? '' : 'style="display:none;"'; ?>>
            <span class="dashicons dashicons-star-empty" style="font-size:2.4em;color:#c3c4c7;"></span>
            <p><?php esc_html_e( 'No profiles saved yet.', 'ai1wm-manager' ); ?></p>
        </div>
    </div>
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
