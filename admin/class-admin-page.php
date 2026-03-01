<?php
/**
 * Admin page controller — enqueues assets and renders the page shell with sidebar nav.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Admin_Page {

    /** @var AI1WM_Manager_Extensions_Manager */
    private $ext;

    /** @var AI1WM_Manager_Settings_Manager */
    private $settings;

    public function __construct() {
        $this->ext      = new AI1WM_Manager_Extensions_Manager();
        $this->settings = new AI1WM_Manager_Settings_Manager();
        // Asset enqueuing is handled by AI1WM_Manager_Core::enqueue_admin_assets()
        // which is registered on admin_enqueue_scripts before this class is instantiated.
    }

    /**
     * Main page render entry point.
     */
    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'ai1wm-manager' ) );
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
        $valid_tabs = array( 'overview', 'extensions', 'settings', 'activity-log', 'plugin-settings' );
        if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
            $active_tab = 'overview';
        }

        $nav_items = array(
            'overview'        => array( 'icon' => 'dashicons-chart-bar',       'label' => __( 'Overview', 'ai1wm-manager' ) ),
            'extensions'      => array( 'icon' => 'dashicons-admin-plugins',   'label' => __( 'Extensions', 'ai1wm-manager' ) ),
            'settings'        => array( 'icon' => 'dashicons-admin-settings',  'label' => __( 'Settings', 'ai1wm-manager' ) ),
            'activity-log'    => array( 'icon' => 'dashicons-list-view',       'label' => __( 'Activity Log', 'ai1wm-manager' ) ),
            'plugin-settings' => array( 'icon' => 'dashicons-admin-generic',   'label' => __( 'Plugin Options', 'ai1wm-manager' ) ),
        );

        ?>
        <div class="ai1wm-wrap">

            <!-- Sidebar -->
            <aside class="ai1wm-sidebar">
                <div class="ai1wm-sidebar-header">
                    <div class="ai1wm-logo">
                        <span class="dashicons dashicons-migrate"></span>
                    </div>
                    <div class="ai1wm-logo-text">
                        <strong>AI1WM</strong>
                        <span><?php esc_html_e( 'Manager', 'ai1wm-manager' ); ?></span>
                    </div>
                </div>

                <nav class="ai1wm-nav">
                    <?php foreach ( $nav_items as $tab_id => $item ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( array( 'page' => AI1WM_MANAGER_SLUG, 'tab' => $tab_id ), admin_url( 'tools.php' ) ) ); ?>"
                           class="ai1wm-nav-item <?php echo $active_tab === $tab_id ? 'active' : ''; ?>"
                           data-tab="<?php echo esc_attr( $tab_id ); ?>">
                            <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                            <span class="ai1wm-nav-label"><?php echo esc_html( $item['label'] ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="ai1wm-sidebar-footer">
                    <span class="ai1wm-version">v<?php echo esc_html( AI1WM_MANAGER_VERSION ); ?></span>
                    <a href="https://github.com/nurkamol/ai1wm-manager" target="_blank" rel="noopener" class="ai1wm-sidebar-link">
                        <span class="dashicons dashicons-external"></span>
                    </a>
                </div>
            </aside>

            <!-- Main content area -->
            <main class="ai1wm-content">
                <div class="ai1wm-content-inner">
                    <?php $this->render_tab( $active_tab ); ?>
                </div>
            </main>

        </div>

        <!-- Toast container -->
        <div id="ai1wm-toast-container" aria-live="polite"></div>

        <!-- Modal overlay -->
        <div id="ai1wm-modal-overlay" class="ai1wm-modal-overlay" style="display:none;">
            <div class="ai1wm-modal">
                <div class="ai1wm-modal-header">
                    <h3 id="ai1wm-modal-title"></h3>
                    <button type="button" class="ai1wm-modal-close" onclick="AI1WM.modal.close()">&times;</button>
                </div>
                <div class="ai1wm-modal-body" id="ai1wm-modal-body"></div>
                <div class="ai1wm-modal-footer" id="ai1wm-modal-footer"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Include the view file for the active tab.
     */
    private function render_tab( $tab ) {
        $view_file = AI1WM_MANAGER_DIR . 'admin/views/page-' . $tab . '.php';
        if ( ! file_exists( $view_file ) ) {
            echo '<div class="ai1wm-card"><p>' . esc_html__( 'Tab not found.', 'ai1wm-manager' ) . '</p></div>';
            return;
        }

        $ext      = $this->ext;
        $settings = $this->settings;

        require $view_file;
    }
}
