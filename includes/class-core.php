<?php
/**
 * Plugin core — singleton that wires all components together.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI1WM_Manager_Core {

    /** @var self|null */
    private static $instance = null;

    /** @var AI1WM_Manager_Scheduler */
    private $scheduler;

    /** @var AI1WM_Manager_Ajax_Handler */
    private $ajax;

    /** @var AI1WM_Manager_Dashboard_Widget */
    private $widget;

    /**
     * Hook suffix returned by add_management_page(), used to scope asset enqueuing.
     * @var string
     */
    private $page_hook = '';

    private function __construct() {
        $this->scheduler = new AI1WM_Manager_Scheduler();
        $this->ajax      = new AI1WM_Manager_Ajax_Handler();
        $this->widget    = new AI1WM_Manager_Dashboard_Widget();

        $this->register_hooks();
    }

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function register_hooks() {
        // Admin page — menu registered at admin_menu, assets enqueued at admin_enqueue_scripts
        add_action( 'admin_menu',            array( $this, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( AI1WM_MANAGER_FILE ), array( $this, 'add_action_links' ) );

        // AJAX endpoints
        $this->ajax->register_hooks();

        // Scheduler (cron)
        $this->scheduler->register_hooks();

        // Dashboard widget
        $this->widget->register_hooks();

        // Log retention prune (once per day)
        add_action( 'wp_scheduled_delete', array( $this, 'maybe_prune_logs' ) );

        // WP-CLI — loaded via the file itself when WP_CLI is defined
        add_action( 'cli_init', function () {
            $cli_file = AI1WM_MANAGER_DIR . 'includes/class-cli.php';
            if ( file_exists( $cli_file ) ) {
                require_once $cli_file;
            }
        } );
    }

    /**
     * Register the admin menu item under Tools.
     * Captures the hook suffix so enqueue_admin_assets() can target exactly this page.
     */
    public function register_admin_menu() {
        $this->page_hook = add_management_page(
            __( 'AI1WM Manager', 'ai1wm-manager' ),
            __( 'AI1WM Manager', 'ai1wm-manager' ),
            'manage_options',
            AI1WM_MANAGER_SLUG,
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue CSS and JS only on our admin page.
     * Runs on admin_enqueue_scripts — which fires BEFORE the page callback,
     * so this must be registered here in Core (not inside the page callback).
     *
     * @param string $hook Current admin page hook suffix.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( $hook !== $this->page_hook ) {
            return;
        }

        wp_enqueue_style(
            'ai1wm-manager-admin',
            AI1WM_MANAGER_URL . 'assets/css/admin.css',
            array(),
            AI1WM_MANAGER_VERSION
        );

        wp_enqueue_script(
            'ai1wm-manager-admin',
            AI1WM_MANAGER_URL . 'assets/js/admin.js',
            array(),
            AI1WM_MANAGER_VERSION,
            true
        );

        wp_localize_script( 'ai1wm-manager-admin', 'ai1wmManager', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( AI1WM_MANAGER_NONCE ),
            'i18n'    => array(
                'confirmDelete'    => __( 'Are you sure you want to delete this backup? This cannot be undone.', 'ai1wm-manager' ),
                'confirmDeleteAll' => __( 'Are you sure you want to delete ALL backups? This cannot be undone.', 'ai1wm-manager' ),
                'confirmRevert'    => __( 'Are you sure you want to revert extensions to this backup?', 'ai1wm-manager' ),
                'confirmRestore'   => __( 'Are you sure you want to restore these settings?', 'ai1wm-manager' ),
                'confirmClearLog'  => __( 'Are you sure you want to clear the entire activity log?', 'ai1wm-manager' ),
                'saving'           => __( 'Saving…', 'ai1wm-manager' ),
                'loading'          => __( 'Loading…', 'ai1wm-manager' ),
            ),
        ) );
    }

    /**
     * Render the admin page by delegating to Admin_Page.
     */
    public function render_admin_page() {
        require_once AI1WM_MANAGER_DIR . 'admin/class-admin-page.php';
        $page = new AI1WM_Manager_Admin_Page();
        $page->render();
    }

    /**
     * Add a "Manage" link to the plugins list.
     */
    public function add_action_links( $links ) {
        $link = '<a href="' . esc_url( admin_url( 'tools.php?page=' . AI1WM_MANAGER_SLUG ) ) . '">'
              . __( 'Manage AI1WM', 'ai1wm-manager' )
              . '</a>';
        array_unshift( $links, $link );
        return $links;
    }

    /**
     * Prune activity logs based on configured retention period.
     */
    public function maybe_prune_logs() {
        $options = get_option( 'ai1wm_manager_options', array() );
        $days    = isset( $options['log_retention_days'] ) ? (int) $options['log_retention_days'] : 60;
        if ( $days > 0 ) {
            AI1WM_Manager_Activity_Log::prune( $days );
        }
    }
}
