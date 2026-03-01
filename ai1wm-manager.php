<?php
/**
 * Plugin Name: All-in-One WP Migration Manager
 * Plugin URI: https://github.com/nurkamol/ai1wm-manager
 * Description: Professional management solution for All-in-One WP Migration — extension version control, settings export/import, scheduled backups, activity log, AJAX operations, WP-CLI support and more.
 * Version: 4.0.0
 * Author: Nurkamol Vakhidov
 * Author URI: https://nurkamol.com
 * Requires at least: 5.6
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * Text Domain: ai1wm-manager
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'AI1WM_MANAGER_VERSION', '4.0.0' );
define( 'AI1WM_MANAGER_FILE',    __FILE__ );
define( 'AI1WM_MANAGER_DIR',     plugin_dir_path( __FILE__ ) );
define( 'AI1WM_MANAGER_URL',     plugin_dir_url( __FILE__ ) );
define( 'AI1WM_MANAGER_SLUG',    'ai1wm-manager' );
define( 'AI1WM_MANAGER_NONCE',   'ai1wm_manager_nonce' );

// Simple class autoloader
spl_autoload_register( function ( $class ) {
    $prefix = 'AI1WM_Manager_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }
    $name = substr( $class, strlen( $prefix ) );
    $file = AI1WM_MANAGER_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Also load Admin class
spl_autoload_register( function ( $class ) {
    if ( $class === 'AI1WM_Manager_Admin_Page' ) {
        $file = AI1WM_MANAGER_DIR . 'admin/class-admin-page.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
} );

// Activation hook
register_activation_hook( __FILE__, function () {
    require_once AI1WM_MANAGER_DIR . 'includes/class-installer.php';
    AI1WM_Manager_Installer::activate();
} );

// Deactivation hook
register_deactivation_hook( __FILE__, function () {
    require_once AI1WM_MANAGER_DIR . 'includes/class-installer.php';
    AI1WM_Manager_Installer::deactivate();
} );

// Boot plugin after all plugins are loaded
add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'ai1wm-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    AI1WM_Manager_Core::instance();
} );
