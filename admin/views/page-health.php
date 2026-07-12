<?php
/**
 * Health / Diagnostics tab — environment and configuration checks.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$health  = new AI1WM_Manager_Health_Check();
$checks  = $health->run();
$summary = AI1WM_Manager_Health_Check::summarize( $checks );

// Overall banner state: error beats warning beats ok.
if ( $summary['error'] > 0 ) {
    $overall_class = 'error';
    $overall_icon  = 'dashicons-dismiss';
    $overall_text  = __( 'Issues found that need attention', 'ai1wm-manager' );
} elseif ( $summary['warning'] > 0 ) {
    $overall_class = 'warning';
    $overall_icon  = 'dashicons-warning';
    $overall_text  = __( 'Everything works, with a few recommendations', 'ai1wm-manager' );
} else {
    $overall_class = 'ok';
    $overall_icon  = 'dashicons-yes-alt';
    $overall_text  = __( 'All systems healthy', 'ai1wm-manager' );
}

$status_meta = array(
    'ok'      => array( 'icon' => 'dashicons-yes-alt',  'label' => __( 'Pass', 'ai1wm-manager' ) ),
    'warning' => array( 'icon' => 'dashicons-warning',  'label' => __( 'Warning', 'ai1wm-manager' ) ),
    'error'   => array( 'icon' => 'dashicons-dismiss',  'label' => __( 'Fail', 'ai1wm-manager' ) ),
);
?>

<div class="ai1wm-page-header">
    <h1><?php esc_html_e( 'Health &amp; Diagnostics', 'ai1wm-manager' ); ?></h1>
    <p class="ai1wm-page-desc"><?php esc_html_e( 'Environment and configuration checks for AI1WM Manager.', 'ai1wm-manager' ); ?></p>
</div>

<div class="ai1wm-health-banner ai1wm-health-banner--<?php echo esc_attr( $overall_class ); ?>">
    <span class="dashicons <?php echo esc_attr( $overall_icon ); ?>"></span>
    <div class="ai1wm-health-banner-text">
        <strong><?php echo esc_html( $overall_text ); ?></strong>
        <span>
            <?php
            printf(
                esc_html__( '%1$d passed · %2$d warnings · %3$d failed', 'ai1wm-manager' ),
                (int) $summary['ok'],
                (int) $summary['warning'],
                (int) $summary['error']
            );
            ?>
        </span>
    </div>
</div>

<div class="ai1wm-card">
    <div class="ai1wm-card-body" style="padding:0;">
        <div class="ai1wm-health-list">
            <?php foreach ( $checks as $check ) :
                $status = $check['status'] ?? 'ok';
                $meta   = $status_meta[ $status ] ?? $status_meta['ok'];
            ?>
            <div class="ai1wm-health-row ai1wm-health-row--<?php echo esc_attr( $status ); ?>">
                <div class="ai1wm-health-status" title="<?php echo esc_attr( $meta['label'] ); ?>">
                    <span class="dashicons <?php echo esc_attr( $meta['icon'] ); ?>"></span>
                </div>
                <div class="ai1wm-health-main">
                    <div class="ai1wm-health-label"><?php echo esc_html( $check['label'] ); ?></div>
                    <div class="ai1wm-health-message"><?php echo esc_html( $check['message'] ); ?></div>
                </div>
                <div class="ai1wm-health-value">
                    <?php echo esc_html( $check['value'] ); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
