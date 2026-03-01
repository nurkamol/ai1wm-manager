<?php
/**
 * Activity Log tab — filterable, server-rendered table with AJAX pagination.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$per_page    = 25;
$page        = max( 1, (int) ( $_GET['log_page'] ?? 1 ) );
$filter_act  = sanitize_key( $_GET['log_action'] ?? '' );
$date_from   = sanitize_text_field( $_GET['log_from'] ?? '' );
$date_to     = sanitize_text_field( $_GET['log_to'] ?? '' );

$result      = AI1WM_Manager_Activity_Log::get_entries( array(
    'per_page'  => $per_page,
    'page'      => $page,
    'action'    => $filter_act,
    'date_from' => $date_from,
    'date_to'   => $date_to,
) );

$entries     = $result['items'];
$total       = $result['total'];
$total_pages = max( 1, (int) ceil( $total / $per_page ) );
$action_types = AI1WM_Manager_Activity_Log::get_action_types();

$base_url = add_query_arg( array( 'page' => AI1WM_MANAGER_SLUG, 'tab' => 'activity-log' ), admin_url( 'tools.php' ) );

$action_labels = array(
    'extension_backup'  => __( 'Extension Backup', 'ai1wm-manager' ),
    'extension_update'  => __( 'Extension Update', 'ai1wm-manager' ),
    'extension_revert'  => __( 'Extension Revert', 'ai1wm-manager' ),
    'settings_export'   => __( 'Settings Export', 'ai1wm-manager' ),
    'settings_import'   => __( 'Settings Import', 'ai1wm-manager' ),
    'settings_backup'   => __( 'Settings Backup', 'ai1wm-manager' ),
    'settings_restore'  => __( 'Settings Restore', 'ai1wm-manager' ),
    'settings_dry_run'  => __( 'Dry-Run Preview', 'ai1wm-manager' ),
    'backup_deleted'    => __( 'Backup Deleted', 'ai1wm-manager' ),
    'backups_cleared'   => __( 'Backups Cleared', 'ai1wm-manager' ),
    'scheduled_backup'  => __( 'Scheduled Backup', 'ai1wm-manager' ),
    'options_saved'     => __( 'Options Saved', 'ai1wm-manager' ),
);
?>

<div class="ai1wm-page-header">
    <h1><?php esc_html_e( 'Activity Log', 'ai1wm-manager' ); ?></h1>
    <p class="ai1wm-page-desc">
        <?php printf( esc_html__( '%d total entries', 'ai1wm-manager' ), $total ); ?>
    </p>
</div>

<!-- Filters -->
<div class="ai1wm-card">
    <div class="ai1wm-card-body">
        <form method="get" class="ai1wm-filter-form">
            <input type="hidden" name="page" value="<?php echo esc_attr( AI1WM_MANAGER_SLUG ); ?>">
            <input type="hidden" name="tab" value="activity-log">

            <div class="ai1wm-filter-row">
                <div class="ai1wm-filter-group">
                    <label class="ai1wm-label" for="log_action"><?php esc_html_e( 'Action', 'ai1wm-manager' ); ?></label>
                    <select name="log_action" id="log_action" class="ai1wm-select">
                        <option value=""><?php esc_html_e( 'All actions', 'ai1wm-manager' ); ?></option>
                        <?php foreach ( $action_types as $act ) : ?>
                            <option value="<?php echo esc_attr( $act ); ?>" <?php selected( $filter_act, $act ); ?>>
                                <?php echo esc_html( $action_labels[ $act ] ?? str_replace( '_', ' ', ucfirst( $act ) ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ai1wm-filter-group">
                    <label class="ai1wm-label" for="log_from"><?php esc_html_e( 'From', 'ai1wm-manager' ); ?></label>
                    <input type="date" name="log_from" id="log_from" value="<?php echo esc_attr( $date_from ); ?>" class="ai1wm-input">
                </div>

                <div class="ai1wm-filter-group">
                    <label class="ai1wm-label" for="log_to"><?php esc_html_e( 'To', 'ai1wm-manager' ); ?></label>
                    <input type="date" name="log_to" id="log_to" value="<?php echo esc_attr( $date_to ); ?>" class="ai1wm-input">
                </div>

                <div class="ai1wm-filter-group ai1wm-filter-actions">
                    <button type="submit" class="ai1wm-btn ai1wm-btn-primary">
                        <span class="dashicons dashicons-filter"></span> <?php esc_html_e( 'Filter', 'ai1wm-manager' ); ?>
                    </button>
                    <a href="<?php echo esc_url( $base_url ); ?>" class="ai1wm-btn ai1wm-btn-ghost">
                        <?php esc_html_e( 'Reset', 'ai1wm-manager' ); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="ai1wm-card">
    <div class="ai1wm-card-header">
        <h2 class="ai1wm-card-title"><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Log Entries', 'ai1wm-manager' ); ?></h2>
        <?php if ( ! empty( $entries ) ) : ?>
        <button type="button" class="ai1wm-btn ai1wm-btn-danger ai1wm-btn-sm" data-action="clearActivityLog">
            <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear All', 'ai1wm-manager' ); ?>
        </button>
        <?php endif; ?>
    </div>
    <div class="ai1wm-card-body ai1wm-card-body--flush">
        <?php if ( empty( $entries ) ) : ?>
            <div class="ai1wm-empty-state">
                <span class="dashicons dashicons-list-view" style="font-size:3em;color:#c3c4c7;"></span>
                <p><?php esc_html_e( 'No log entries found.', 'ai1wm-manager' ); ?></p>
            </div>
        <?php else : ?>
            <table class="ai1wm-table">
                <thead>
                    <tr>
                        <th style="width:155px;"><?php esc_html_e( 'Date', 'ai1wm-manager' ); ?></th>
                        <th style="width:110px;"><?php esc_html_e( 'User', 'ai1wm-manager' ); ?></th>
                        <th style="width:140px;"><?php esc_html_e( 'Action', 'ai1wm-manager' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'ai1wm-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $entries as $entry ) : ?>
                    <tr>
                        <td class="ai1wm-text-muted ai1wm-nowrap"><?php echo esc_html( $entry['created_at'] ); ?></td>
                        <td><?php echo esc_html( $entry['user_login'] ?: '—' ); ?></td>
                        <td>
                            <span class="ai1wm-action-badge ai1wm-action-<?php echo esc_attr( $entry['action'] ); ?>">
                                <?php echo esc_html( $action_labels[ $entry['action'] ] ?? str_replace( '_', ' ', ucfirst( $entry['action'] ) ) ); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo esc_html( $entry['description'] ); ?>
                            <?php if ( ! empty( $entry['context'] ) && $entry['context'] !== '[]' ) : ?>
                                <button type="button" class="ai1wm-link-btn"
                                        onclick="AI1WM.modal.open('<?php esc_attr_e( 'Context', 'ai1wm-manager' ); ?>', '<pre style=\'white-space:pre-wrap;font-size:12px;\'>' + AI1WM.escHtml('<?php echo esc_js( $entry['context'] ); ?>') + '</pre>', [])">
                                    <?php esc_html_e( 'Details', 'ai1wm-manager' ); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ( $total_pages > 1 ) : ?>
            <div class="ai1wm-pagination">
                <?php if ( $page > 1 ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( array( 'log_page' => $page - 1, 'log_action' => $filter_act, 'log_from' => $date_from, 'log_to' => $date_to ), $base_url ) ); ?>"
                       class="ai1wm-page-btn">&laquo; <?php esc_html_e( 'Prev', 'ai1wm-manager' ); ?></a>
                <?php endif; ?>

                <span class="ai1wm-page-info">
                    <?php printf( esc_html__( 'Page %1$d of %2$d', 'ai1wm-manager' ), $page, $total_pages ); ?>
                </span>

                <?php if ( $page < $total_pages ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( array( 'log_page' => $page + 1, 'log_action' => $filter_act, 'log_from' => $date_from, 'log_to' => $date_to ), $base_url ) ); ?>"
                       class="ai1wm-page-btn"><?php esc_html_e( 'Next', 'ai1wm-manager' ); ?> &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
