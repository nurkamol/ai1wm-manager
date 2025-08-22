<?php
/**
 * Plugin Name: All-in-One WP Migration Manager
 * Plugin URI: https://github.com/nurkamol/ai1wm-manager
 * Description: Complete management solution for All-in-One WP Migration plugin - manage extension versions, export/import settings, and configure plugin options with enhanced security and backup features.
 * Version: 3.1.1
 * Author: Nurkamol Vakhidov
 * Author URI: https://nurkamol.com
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Network: false
 * License: GPL v2 or later
 * Text Domain: ai1wm-manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AI1WM_Manager {
    
    private $plugin_slug = 'ai1wm-manager';
    private $nonce_action = 'ai1wm_manager_action';
    private $version = '3.1.1';
    
    // Changelog data
    private $changelog = array(
        '3.1.1' => array(
            'date' => '2025-07-26',
            'type' => 'hotfix',
            'changes' => array(
                'Fixed' => array(
                    'WordPress Core Conflict: Resolved "Security check failed" error when updating plugins or performing bulk operations',
                    'Action Handler Isolation: Plugin now only processes its own actions, avoiding interference with WordPress core functionality',
                    'Nonce Verification Scope: Limited nonce checks to plugin-specific pages and actions only'
                ),
                'Changed' => array(
                    'Hook Priority: Moved admin_init hook to lower priority (20) to prevent conflicts',
                    'Page-Specific Processing: Actions only processed when on plugin\'s admin page',
                    'Cleanup Timing: Backup cleanup only runs on plugin pages to avoid conflicts'
                )
            )
        ),
        '3.1.0' => array(
            'date' => '2025-07-26',
            'type' => 'feature',
            'changes' => array(
                'Added' => array(
                    'Individual Backup Management: Remove specific backups with confirmation dialogs',
                    'Bulk Backup Removal: Clear all backups at once with safety confirmations',
                    'Visual Backup List: Organized cards showing timestamps and backup types',
                    'Smart Data Filtering: Automatic exclusion of site-specific data from exports/display',
                    'Total Backup Counter: Real-time backup count in system status',
                    'Extension Backup Management: Individual and bulk removal for extension backups'
                ),
                'Changed' => array(
                    'Export Filtering: Site-specific options (backup paths, security keys, timestamps) now excluded',
                    'Import Safety: Enhanced protection against importing problematic site-specific data',
                    'Data Portability: Exports contain only clean, transferable settings'
                ),
                'Security' => array(
                    'Data Isolation: Site-specific security keys and paths completely isolated from exports',
                    'Safe Exports: Automatic filtering prevents exposure of sensitive data',
                    'Enhanced Import Protection: Better validation against dangerous options'
                )
            )
        ),
        '3.0.0' => array(
            'date' => '2025-07-25',
            'type' => 'major',
            'changes' => array(
                'Added' => array(
                    'Major Plugin Unification: Combined extension manager and settings manager into one comprehensive solution',
                    'Tabbed Interface: Clean navigation with Overview, Extensions, and Settings tabs',
                    'Interactive Dashboard: Clickable feature cards for easy navigation',
                    'Enhanced Security: Improved data validation and sanitization throughout',
                    'Smart JSON Cleanup: Automatic removal of bloated extension metadata from exports'
                ),
                'Changed' => array(
                    'Plugin Name: Renamed from "All-in-One WP Migration Complete Manager" to "All-in-One WP Migration Manager"',
                    'JavaScript Implementation: Switched from jQuery to vanilla JavaScript for better reliability',
                    'Export Format: Optimized JSON output to match clean format standards'
                ),
                'Fixed' => array(
                    'Tab Navigation: Resolved issues with Extensions and Settings tabs not switching properly',
                    'Feature Card Interaction: Fixed clickable cards not responding correctly',
                    'JSON Bloat: Fixed export files containing unnecessary extension metadata'
                )
            )
        )
    );
    
    // Extension versions from the provided list
    private $extension_versions = array(
        'AI1WMZE' => '',      // Microsoft Azure Extension
        'AI1WMAE' => '1.45',  // Backblaze B2
        'AI1WMVE' => '',      // Backup Plugin
        'AI1WMBE' => '1.48',  // Box
        'AI1WMIE' => '',      // DigitalOcean Spaces Extension
        'AI1WMXE' => '',      // Direct Extension
        'AI1WMDE' => '3.71',  // Dropbox
        'AI1WMTE' => '1.8',   // File
        'AI1WMFE' => '2.84',  // FTP
        'AI1WMCE' => '',      // Google Cloud Storage Extension
        'AI1WMGE' => '2.92',  // Google Drive
        'AI1WMRE' => '',      // Amazon Glacier Extension
        'AI1WMEE' => '',      // Mega Extension
        'AI1WMME' => '4.40',  // Multisite
        'AI1WMOE' => '1.69',  // OneDrive
        'AI1WMPE' => '',      // pCloud Extension
        'AI1WMKE' => '',      // Pro Plugin
        'AI1WMNE' => '1.40',  // S3 Client
        'AI1WMSE' => '3.81',  // Amazon S3
        'AI1WMUE' => '2.65',  // Unlimited
        'AI1WMLE' => '2.71',  // URL
        'AI1WMWE' => '',      // WebDAV Extension
    );

    // Extension names mapping
    private $extension_names = array(
        'AI1WMZE' => 'Microsoft Azure Extension',
        'AI1WMAE' => 'Backblaze B2 Extension',
        'AI1WMVE' => 'Backup Plugin',
        'AI1WMBE' => 'Box Extension',
        'AI1WMIE' => 'DigitalOcean Spaces Extension',
        'AI1WMXE' => 'Direct Extension',
        'AI1WMDE' => 'Dropbox Extension',
        'AI1WMTE' => 'File Extension',
        'AI1WMFE' => 'FTP Extension',
        'AI1WMCE' => 'Google Cloud Storage Extension',
        'AI1WMGE' => 'Google Drive Extension',
        'AI1WMRE' => 'Amazon Glacier Extension',
        'AI1WMEE' => 'Mega Extension',
        'AI1WMME' => 'Multisite Extension',
        'AI1WMOE' => 'OneDrive Extension',
        'AI1WMPE' => 'pCloud Extension',
        'AI1WMKE' => 'Pro Plugin',
        'AI1WMNE' => 'S3 Client Extension',
        'AI1WMSE' => 'Amazon S3 Extension',
        'AI1WMUE' => 'Unlimited Extension',
        'AI1WMLE' => 'URL Extension',
        'AI1WMWE' => 'WebDAV Extension',
    );

    // File paths
    private $extensions_file_path;
    private $backup_data = array();
    private $backup_option_name = 'ai1wm_manager_backup';

    public function __construct() {
        $this->extensions_file_path = WP_CONTENT_DIR . '/plugins/all-in-one-wp-migration/lib/model/class-ai1wm-extensions.php';
        
        // Initialize hooks - but only on our admin pages to avoid conflicts
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Only handle actions on our specific page
        add_action('admin_init', array($this, 'handle_actions'), 20); // Lower priority to avoid conflicts
        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        
        // Load backup data
        $this->backup_data = get_option($this->backup_option_name, array());
        
        // Clean up any existing backup duplicates on plugin load
        add_action('admin_init', array($this, 'cleanup_backup_duplicates'), 5);
    }

    /**
     * Add action links to plugins page
     */
    public function add_action_links($links) {
        $manage_link = '<a href="' . admin_url('tools.php?page=' . $this->plugin_slug) . '">Manage AI1WM</a>';
        array_unshift($links, $manage_link);
        return $links;
    }

    /**
     * Register admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'All-in-One WP Migration Manager',
            'AI1WM Manager',
            'manage_options',
            $this->plugin_slug,
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, $this->plugin_slug) === false) {
            return;
        }
        
        // Add styling
        wp_add_inline_style('wp-admin', '
            .ai1wm-manager .notice { margin: 20px 0; }
            .ai1wm-manager pre { 
                background: #f1f1f1; 
                padding: 15px; 
                border-radius: 4px; 
                max-height: 500px; 
                overflow-y: auto; 
                white-space: pre-wrap;
                word-break: break-all;
            }
            .ai1wm-manager .form-section { 
                background: #fff; 
                padding: 20px; 
                margin: 20px 0; 
                border: 1px solid #ccd0d4; 
                border-radius: 4px;
            }
            .ai1wm-manager .warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 10px;
                border-radius: 4px;
                margin: 10px 0;
            }
            .ai1wm-manager .nav-tab-wrapper {
                margin-bottom: 20px;
            }
            .ai1wm-manager .form-table {
                border-collapse: collapse;
                margin-top: 15px;
                width: 100%;
            }
            .ai1wm-manager .form-table th, 
            .ai1wm-manager .form-table td {
                border-bottom: 1px solid #f0f0f0;
                padding: 10px;
                text-align: left;
            }
            .ai1wm-manager .form-table thead th {
                background: #f9f9f9;
            }
            .ai1wm-manager .feature-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .ai1wm-manager .feature-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .ai1wm-manager .feature-card:hover {
                border-color: #0073aa;
                box-shadow: 0 2px 8px rgba(0,115,170,0.1);
                transform: translateY(-2px);
            }
            .ai1wm-manager .feature-icon {
                font-size: 3em;
                margin-bottom: 10px;
            }
            .ai1wm-manager .tab-content {
                display: none;
            }
            .ai1wm-manager .tab-content.active {
                display: block;
            }
            .ai1wm-manager .backup-item {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                margin: 10px 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .ai1wm-manager .backup-info {
                flex-grow: 1;
            }
            .ai1wm-manager .backup-actions {
                display: flex;
                gap: 10px;
            }
            .ai1wm-manager .backup-date {
                font-weight: bold;
                color: #333;
            }
            .ai1wm-manager .backup-type {
                color: #666;
                font-size: 0.9em;
            }
            .ai1wm-manager .backup-list {
                max-height: 400px;
                overflow-y: auto;
            }
            .ai1wm-manager .remove-backup-btn {
                background: #dc3545;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
            }
            .ai1wm-manager .remove-backup-btn:hover {
                background: #c82333;
            }
            .ai1wm-manager .remove-all-btn {
                background: #dc3545;
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 4px;
                cursor: pointer;
                margin-bottom: 15px;
            }
            .ai1wm-manager .remove-all-btn:hover {
                background: #c82333;
            }
            .ai1wm-manager .changelog-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                margin: 20px 0;
            }
            .ai1wm-manager .changelog-header {
                background: #f9f9f9;
                padding: 15px 20px;
                border-bottom: 1px solid #ccd0d4;
                display: flex;
                justify-content: space-between;
                align-items: center;
                cursor: pointer;
            }
            .ai1wm-manager .changelog-content {
                padding: 20px;
                display: none;
                max-height: 400px;
                overflow-y: auto;
            }
            .ai1wm-manager .changelog-content.open {
                display: block;
            }
            .ai1wm-manager .version-entry {
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            .ai1wm-manager .version-entry:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }
            .ai1wm-manager .version-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 10px;
            }
            .ai1wm-manager .version-number {
                font-weight: bold;
                color: #0073aa;
            }
            .ai1wm-manager .version-date {
                color: #666;
                font-size: 0.9em;
            }
            .ai1wm-manager .version-type {
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 0.8em;
                text-transform: uppercase;
                font-weight: bold;
            }
            .ai1wm-manager .version-type.major {
                background: #e74c3c;
                color: white;
            }
            .ai1wm-manager .version-type.feature {
                background: #3498db;
                color: white;
            }
            .ai1wm-manager .version-type.hotfix {
                background: #f39c12;
                color: white;
            }
            .ai1wm-manager .change-category {
                margin-bottom: 10px;
            }
            .ai1wm-manager .change-category h4 {
                margin: 0 0 5px 0;
                color: #333;
                font-size: 0.9em;
            }
            .ai1wm-manager .change-list {
                margin: 0 0 0 15px;
                padding: 0;
            }
            .ai1wm-manager .change-list li {
                margin-bottom: 3px;
                font-size: 0.9em;
                line-height: 1.4;
            }
            .ai1wm-manager .toggle-icon {
                transition: transform 0.3s ease;
            }
            .ai1wm-manager .toggle-icon.open {
                transform: rotate(180deg);
            }
        ');
    }

    /**
     * Handle form actions
     */
    public function handle_actions() {
        // Only handle our plugin's actions - avoid interfering with WordPress core actions
        if (!isset($_POST['action']) || !isset($_POST['_wpnonce'])) {
            return;
        }
        
        // Only process if this is our plugin's page
        if (!isset($_GET['page']) || $_GET['page'] !== $this->plugin_slug) {
            return;
        }
        
        // Only handle our specific actions
        $our_actions = array(
            'backup_extensions', 'update_extensions', 'revert_extensions',
            'export_settings', 'import_settings', 'backup_settings',
            'remove_backup', 'remove_all_backups', 
            'remove_extension_backup', 'remove_all_extension_backups'
        );
        
        if (!in_array($_POST['action'], $our_actions)) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['_wpnonce'], $this->nonce_action)) {
            wp_die('Security check failed.');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }
        
        switch ($_POST['action']) {
            case 'backup_extensions':
                $this->backup_extensions();
                break;
            case 'update_extensions':
                $this->update_extensions();
                break;
            case 'revert_extensions':
                $this->revert_extensions();
                break;
            case 'export_settings':
                $this->handle_export_settings();
                break;
            case 'import_settings':
                $this->handle_import_settings();
                break;
            case 'backup_settings':
                $this->handle_backup_settings();
                break;
            case 'remove_backup':
                $this->handle_remove_backup();
                break;
            case 'remove_all_backups':
                $this->handle_remove_all_backups();
                break;
            case 'remove_extension_backup':
                $this->handle_remove_extension_backup();
                break;
            case 'remove_all_extension_backups':
                $this->handle_remove_all_extension_backups();
                break;
        }
    }

    /**
     * Clean up backup duplicates
     */
    public function cleanup_backup_duplicates() {
        // Only run on our plugin pages to avoid interfering with other operations
        if (!isset($_GET['page']) || $_GET['page'] !== $this->plugin_slug) {
            return;
        }
        
        if (get_option('ai1wm_manager_cleaned_duplicates')) {
            return;
        }
        
        global $wpdb;
        
        $backup_entries = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'ai1wm_inspector_backup_%'",
            ARRAY_A
        );
        
        foreach ($backup_entries as $entry) {
            delete_option($entry['option_name']);
        }
        
        update_option('ai1wm_manager_cleaned_duplicates', true);
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied.');
        }

        $file_exists = file_exists($this->extensions_file_path);
        $current_versions = $this->get_current_extension_versions();
        $settings = $this->get_ai1wm_settings(true);
        $total_settings = count($settings);
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        $all_backups = $this->get_all_backups();

        ?>
        <div class="wrap ai1wm-manager">
            <h1>🚀 All-in-One WP Migration Manager</h1>
            <p>Complete management solution for your All-in-One WP Migration plugin and extensions.</p>
            
            <?php if (isset($_GET['backup_removed']) && $_GET['backup_removed'] == 'success'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Backup removed successfully.</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['all_backups_removed']) && $_GET['all_backups_removed'] == 'success'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Successfully removed <?php echo isset($_GET['count']) ? intval($_GET['count']) : ''; ?> backup(s).</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['backup_error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p>
                        <?php 
                        switch ($_GET['backup_error']) {
                            case 'no_key':
                                echo 'No backup specified for removal.';
                                break;
                            case 'invalid_key':
                                echo 'Invalid backup key.';
                                break;
                            case 'delete_failed':
                                echo 'Failed to remove backup.';
                                break;
                            case 'no_backups':
                                echo 'No backups found to remove.';
                                break;
                            default:
                                echo 'An error occurred while removing backup.';
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <h2 class="nav-tab-wrapper">
                <a href="#overview" class="nav-tab nav-tab-active" onclick="switchTab(event, 'overview')">📊 Overview</a>
                <a href="#extensions" class="nav-tab" onclick="switchTab(event, 'extensions')">🧩 Extensions</a>
                <a href="#settings" class="nav-tab" onclick="switchTab(event, 'settings')">⚙️ Settings</a>
            </h2>

            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <div class="feature-grid">
                    <div class="feature-card" onclick="switchTab(null, 'extensions')">
                        <div class="feature-icon">🧩</div>
                        <h3>Extension Manager</h3>
                        <p>Manage and update All-in-One WP Migration extension versions with backup and restore capabilities.</p>
                        <p><small>Click to manage extensions</small></p>
                    </div>
                    <div class="feature-card" onclick="switchTab(null, 'settings')">
                        <div class="feature-icon">⚙️</div>
                        <h3>Settings Manager</h3>
                        <p>Export, import, and backup your AI1WM plugin settings with enhanced security features.</p>
                        <p><small>Click to manage settings</small></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <h3>Security Features</h3>
                        <p>Automatic data redaction, backup creation, and secure import/export operations.</p>
                        <p><small>Built-in security features</small></p>
                    </div>
                </div>

                <div class="form-section">
                    <h2>📈 System Status</h2>
                    <table class="form-table">
                        <tr>
                            <th>Plugin Version</th>
                            <td><?php echo esc_html($this->version); ?></td>
                        </tr>
                        <tr>
                            <th>Extensions File</th>
                            <td>
                                <?php if ($file_exists): ?>
                                    <span style="color: green;">✅ Found</span>
                                <?php else: ?>
                                    <span style="color: red;">❌ Not Found</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>AI1WM Settings Found</th>
                            <td><?php echo $total_settings; ?> settings</td>
                        </tr>
                        <tr>
                            <th>Last Backup</th>
                            <td>
                                <?php if (!empty($this->backup_data)): ?>
                                    <?php echo esc_html(date('Y-m-d H:i:s', $this->backup_data['timestamp'])); ?>
                                <?php else: ?>
                                    No backup available
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Total Backups</th>
                            <td><?php echo count($all_backups); ?> backups</td>
                        </tr>
                    </table>
                </div>
                
                <?php $this->render_changelog_section(); ?>
                
                <?php if (!empty($all_backups)): ?>
                <div class="form-section">
                    <h2>💾 Backup Management</h2>
                    <p>Manage your existing backups. You can remove individual backups or clear all backups at once.</p>
                    
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field($this->nonce_action); ?>
                        <input type="hidden" name="action" value="remove_all_backups">
                        <button type="submit" class="remove-all-btn" onclick="return confirm('Are you sure you want to remove ALL backups? This action cannot be undone.')">
                            🗑️ Remove All Backups (<?php echo count($all_backups); ?>)
                        </button>
                    </form>
                    
                    <div class="backup-list">
                        <?php foreach ($all_backups as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <div class="backup-date"><?php echo esc_html(date('Y-m-d H:i:s', $backup['timestamp'])); ?></div>
                                    <div class="backup-type"><?php echo esc_html($backup['type']); ?></div>
                                </div>
                                <div class="backup-actions">
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field($this->nonce_action); ?>
                                        <input type="hidden" name="action" value="remove_backup">
                                        <input type="hidden" name="backup_key" value="<?php echo esc_attr($backup['option_name']); ?>">
                                        <button type="submit" class="remove-backup-btn" onclick="return confirm('Remove this backup?')">
                                            🗑️ Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Extensions Tab -->
            <div id="extensions" class="tab-content">
                <?php if (!$file_exists): ?>
                    <div class="notice notice-error">
                        <p>Error: The file at <code><?php echo esc_html($this->extensions_file_path); ?></code> does not exist. Please make sure All-in-One WP Migration plugin is installed.</p>
                    </div>
                <?php else: ?>
                    <?php $this->render_extensions_section($current_versions); ?>
                <?php endif; ?>
            </div>

            <!-- Settings Tab -->
            <div id="settings" class="tab-content">
                <?php $this->render_settings_section($settings, $total_settings); ?>
            </div>

            <script>
            function switchTab(event, tabName) {
                if (event) {
                    event.preventDefault();
                }
                
                // Hide all tab content
                var tabContents = document.querySelectorAll('.tab-content');
                for (var i = 0; i < tabContents.length; i++) {
                    tabContents[i].classList.remove('active');
                }
                
                // Remove active class from all tabs
                var tabs = document.querySelectorAll('.nav-tab');
                for (var i = 0; i < tabs.length; i++) {
                    tabs[i].classList.remove('nav-tab-active');
                }
                
                // Show selected tab content
                document.getElementById(tabName).classList.add('active');
                
                // Add active class to clicked tab
                var activeTab = document.querySelector('.nav-tab[href="#' + tabName + '"]');
                if (activeTab) {
                    activeTab.classList.add('nav-tab-active');
                }
            }
            
            function toggleChangelog() {
                var content = document.querySelector('.changelog-content');
                var icon = document.querySelector('.toggle-icon');
                
                if (content.classList.contains('open')) {
                    content.classList.remove('open');
                    icon.classList.remove('open');
                } else {
                    content.classList.add('open');
                    icon.classList.add('open');
                }
            }
            </script>
        </div>
        <?php
    }

    /**
     * Render extensions management section
     */
    private function render_extensions_section($current_versions) {
        $extension_backups = $this->get_extension_backups();
        ?>
        <?php if (isset($_GET['backup']) && $_GET['backup'] == 'success'): ?>
            <div class="notice notice-success is-dismissible">
                <p>Current extension versions have been backed up successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
            <div class="notice notice-success is-dismissible">
                <p>Extension versions have been updated successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['revert']) && $_GET['revert'] == 'success'): ?>
            <div class="notice notice-success is-dismissible">
                <p>Extension versions have been reverted to backup successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['extension_backup_removed']) && $_GET['extension_backup_removed'] == 'success'): ?>
            <div class="notice notice-success is-dismissible">
                <p>Extension backup removed successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['all_extension_backups_removed']) && $_GET['all_extension_backups_removed'] == 'success'): ?>
            <div class="notice notice-success is-dismissible">
                <p>Successfully removed <?php echo isset($_GET['count']) ? intval($_GET['count']) : ''; ?> extension backup(s).</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['extension_backup_error'])): ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <?php 
                    switch ($_GET['extension_backup_error']) {
                        case 'no_key':
                            echo 'No extension backup specified for removal.';
                            break;
                        case 'invalid_key':
                            echo 'Invalid extension backup key.';
                            break;
                        case 'delete_failed':
                            echo 'Failed to remove extension backup.';
                            break;
                        case 'no_backups':
                            echo 'No extension backups found to remove.';
                            break;
                        default:
                            echo 'An error occurred while removing extension backup.';
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2>Step 1: Backup Current Versions</h2>
            <p>Create a backup of current extension versions before making changes.</p>
            <form method="post">
                <input type="hidden" name="action" value="backup_extensions">
                <?php wp_nonce_field($this->nonce_action); ?>
                <p>
                    <input type="submit" class="button button-primary" value="Backup Current Versions">
                    <?php if (!empty($this->backup_data)): ?>
                        <span class="description">Last backup: <?php echo esc_html(date('Y-m-d H:i:s', $this->backup_data['timestamp'])); ?></span>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <div class="form-section">
            <h2>Step 2: Update Extension Versions</h2>
            <p>Check the extensions you want to update and enter the new version numbers.</p>
            <form method="post">
                <input type="hidden" name="action" value="update_extensions">
                <?php wp_nonce_field($this->nonce_action); ?>
                
                <table class="form-table">
                    <thead>
                        <tr>
                            <th>Update</th>
                            <th>Extension</th>
                            <th>Current Version</th>
                            <th>New Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->extension_versions as $prefix => $version): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="update_extensions[<?php echo esc_attr($prefix); ?>][enabled]" value="1">
                                </td>
                                <td><?php echo esc_html($this->extension_names[$prefix]); ?></td>
                                <td>
                                    <?php 
                                    if (isset($current_versions[$prefix])) {
                                        echo esc_html($current_versions[$prefix]);
                                    } else {
                                        echo '<span style="color: #999;">Not found</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <input type="text" name="update_extensions[<?php echo esc_attr($prefix); ?>][version]" 
                                           value="<?php echo esc_attr($version); ?>" class="small-text" size="6">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p>
                    <input type="submit" class="button button-primary" value="Update Selected Versions">
                </p>
            </form>
        </div>
        
        <?php if (!empty($this->backup_data)): ?>
        <div class="form-section">
            <h2>Step 3: Revert to Backup (If Needed)</h2>
            <p>Restore the extension versions from your last backup.</p>
            <form method="post">
                <input type="hidden" name="action" value="revert_extensions">
                <?php wp_nonce_field($this->nonce_action); ?>
                <p>
                    <input type="submit" class="button button-secondary" value="Revert to Backup">
                    <span class="description">Backup from: <?php echo esc_html(date('Y-m-d H:i:s', $this->backup_data['timestamp'])); ?></span>
                </p>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($extension_backups)): ?>
        <div class="form-section">
            <h2>💾 Extension Backup Management</h2>
            <p>Manage your extension backups. You can remove individual backups or clear all extension backups at once.</p>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field($this->nonce_action); ?>
                <input type="hidden" name="action" value="remove_all_extension_backups">
                <button type="submit" class="remove-all-btn" onclick="return confirm('Are you sure you want to remove ALL extension backups? This action cannot be undone.')">
                    🗑️ Remove All Extension Backups (<?php echo count($extension_backups); ?>)
                </button>
            </form>
            
            <div class="backup-list">
                <?php foreach ($extension_backups as $backup): ?>
                    <div class="backup-item">
                        <div class="backup-info">
                            <div class="backup-date"><?php echo esc_html(date('Y-m-d H:i:s', $backup['timestamp'])); ?></div>
                            <div class="backup-type"><?php echo esc_html($backup['type']); ?></div>
                            <?php if (!empty($backup['versions_count'])): ?>
                                <div class="backup-type">Versions stored: <?php echo esc_html($backup['versions_count']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="backup-actions">
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field($this->nonce_action); ?>
                                <input type="hidden" name="action" value="remove_extension_backup">
                                <input type="hidden" name="backup_key" value="<?php echo esc_attr($backup['option_name']); ?>">
                                <button type="submit" class="remove-backup-btn" onclick="return confirm('Remove this extension backup?')">
                                    🗑️ Remove
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render settings management section
     */
    private function render_settings_section($settings, $total_settings) {
        ?>
        <?php if ($total_settings === 0): ?>
            <div class="notice notice-warning">
                <p><strong>⚠️ No AI1WM settings found.</strong> Make sure the All-in-One WP Migration plugin is installed and configured.</p>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2>📥 Export Settings</h2>
            <p>Export your AI1WM plugin settings and configurations to a JSON file for backup or transfer purposes.</p>
            <p><strong>Note:</strong> Site-specific data (backup paths, security keys, timestamps) and extension versioning data are automatically excluded for clean, portable exports.</p>
            
            <form method="post">
                <?php wp_nonce_field($this->nonce_action); ?>
                <input type="hidden" name="action" value="export_settings">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Options</th>
                        <td>
                            <label>
                                <input type="checkbox" name="redact" value="1"> 
                                Redact sensitive values
                            </label><br>
                            <label>
                                <input type="checkbox" name="include_metadata" value="1" checked> 
                                Include export metadata
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">📥 Export Settings</button>
                </p>
            </form>
        </div>
        
        <div class="form-section">
            <h2>📤 Import Settings</h2>
            <div class="warning">
                <strong>⚠️ Warning:</strong> Importing settings will overwrite your current AI1WM plugin configuration. 
                A backup will be created automatically before import. Extension versioning data will be preserved and not affected by imports.
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field($this->nonce_action); ?>
                <input type="hidden" name="action" value="import_settings">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">JSON File</th>
                        <td>
                            <input type="file" name="json_file" accept=".json" required>
                            <p class="description">Maximum file size: 10MB</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-secondary">📤 Import Settings</button>
                </p>
            </form>
        </div>
        
        <div class="form-section">
            <h2>💾 Create Backup</h2>
            <p>Create a complete backup of your current AI1WM plugin settings and configurations.</p>
            <p><strong>Note:</strong> This creates a settings-only backup. Extension versioning data is managed separately in the Extensions tab.</p>
            
            <form method="post">
                <?php wp_nonce_field($this->nonce_action); ?>
                <input type="hidden" name="action" value="backup_settings">
                
                <p class="submit">
                    <button type="submit" class="button">💾 Download Backup</button>
                </p>
            </form>
        </div>
        
        <?php if ($total_settings > 0): ?>
        <div class="form-section">
            <h2>📄 Current Settings (<?php echo $total_settings; ?> found)</h2>
            <p>Below are your current AI1WM settings. <strong>Note:</strong> Site-specific data (backup paths, security keys, timestamps) and sensitive data are automatically excluded from this view for security and portability.</p>
            <pre><?php echo esc_html(print_r($settings, true)); ?></pre>
        </div>
        <?php endif; ?>
        <?php
    }

    // Extension Management Methods

    /**
     * Get current versions from the extensions file
     */
    private function get_current_extension_versions() {
        if (!file_exists($this->extensions_file_path)) {
            return array();
        }

        $content = file_get_contents($this->extensions_file_path);
        $versions = array();

        foreach ($this->extension_versions as $prefix => $version) {
            if (strpos($content, $prefix . "_PLUGIN_NAME") !== false) {
                preg_match("/" . $prefix . "_PLUGIN_NAME.*?'version'\s*=>\s*" . $prefix . "_VERSION/s", $content, $ext_matches);
                
                if (!empty($ext_matches[0])) {
                    preg_match("/'requires'\s*=>\s*['\"]([\d\.]+)['\"].*?,/s", $content, $req_matches, 0, strpos($content, $ext_matches[0]));
                    
                    if (!empty($req_matches[1])) {
                        $versions[$prefix] = $req_matches[1];
                        continue;
                    }
                }
            }

            $patterns = array(
                "/define\s*\(\s*['\"]" . $prefix . "_VERSION['\"]\s*,\s*['\"]([\d\.]+)['\"]\s*\)/",
                "/'requires'\s*=>\s*['\"]([\d\.]+)['\"]/"
            );
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $versions[$prefix] = $matches[1];
                    break;
                }
            }
        }

        return $versions;
    }

    /**
     * Backup current extensions
     */
    public function backup_extensions() {
        $this->backup_data = array(
            'timestamp' => time(),
            'content' => file_exists($this->extensions_file_path) ? file_get_contents($this->extensions_file_path) : '',
            'versions' => $this->get_current_extension_versions()
        );

        update_option($this->backup_option_name, $this->backup_data);

        wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&backup=success'));
        exit;
    }

    /**
     * Render changelog section
     */
    private function render_changelog_section() {
        ?>
        <div class="changelog-section">
            <div class="changelog-header" onclick="toggleChangelog()">
                <div>
                    <h2 style="margin: 0;">📋 Changelog</h2>
                    <p style="margin: 5px 0 0 0; color: #666;">View recent updates and changes</p>
                </div>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="changelog-content">
                <?php foreach ($this->changelog as $version => $info): ?>
                    <div class="version-entry">
                        <div class="version-header">
                            <span class="version-number">v<?php echo esc_html($version); ?></span>
                            <span class="version-date"><?php echo esc_html($info['date']); ?></span>
                            <span class="version-type <?php echo esc_attr($info['type']); ?>"><?php echo esc_html($info['type']); ?></span>
                        </div>
                        
                        <?php foreach ($info['changes'] as $category => $changes): ?>
                            <div class="change-category">
                                <h4>
                                    <?php 
                                    $icons = array(
                                        'Added' => '✅',
                                        'Changed' => '🔄', 
                                        'Fixed' => '🐛',
                                        'Security' => '🔒',
                                        'Removed' => '❌'
                                    );
                                    echo isset($icons[$category]) ? $icons[$category] . ' ' : '';
                                    echo esc_html($category);
                                    ?>
                                </h4>
                                <ul class="change-list">
                                    <?php foreach ($changes as $change): ?>
                                        <li><?php echo esc_html($change); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Update extensions
     */
    public function update_extensions() {
        if (!file_exists($this->extensions_file_path)) {
            wp_die('Extension file not found');
        }

        $content = file_get_contents($this->extensions_file_path);
        $update_count = 0;

        if (isset($_POST['update_extensions']) && is_array($_POST['update_extensions'])) {
            foreach ($_POST['update_extensions'] as $prefix => $data) {
                if (isset($data['enabled']) && $data['enabled'] == '1' && isset($data['version'])) {
                    $new_version = esc_attr($data['version']);
                    $replace_count = 0;
                    
                    $start_pos = strpos($content, $prefix . "_PLUGIN_NAME");
                    
                    if ($start_pos !== false) {
                        $section_end = strpos($content, "if ( defined", $start_pos + 10);
                        if ($section_end === false) $section_end = strlen($content);
                        
                        $section = substr($content, $start_pos, $section_end - $start_pos);
                        
                        $updated_section = preg_replace(
                            "/'requires'\s*=>\s*['\"]([\d\.]+)['\"].*?,/s", 
                            "'requires' => '{$new_version}',", 
                            $section, 
                            -1, 
                            $replace_count
                        );
                        
                        if ($replace_count > 0) {
                            $content = str_replace($section, $updated_section, $content);
                            $update_count += $replace_count;
                        }
                    }
                }
            }
        }

        if ($update_count > 0) {
            if (file_put_contents($this->extensions_file_path, $content) === false) {
                wp_die('Failed to write to file');
            }
        }

        wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&update=success'));
        exit;
    }

    /**
     * Revert extensions
     */
    public function revert_extensions() {
        if (empty($this->backup_data) || empty($this->backup_data['content'])) {
            wp_die('No backup available');
        }

        if (file_put_contents($this->extensions_file_path, $this->backup_data['content']) === false) {
            wp_die('Failed to write to file');
        }

        wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&revert=success'));
        exit;
    }

    // Settings Management Methods

    /**
     * Handle settings export
     */
    private function handle_export_settings() {
        $should_redact = isset($_POST['redact']) && $_POST['redact'] === '1';
        $include_metadata = isset($_POST['include_metadata']) && $_POST['include_metadata'] === '1';
        
        $data = $this->get_ai1wm_settings($should_redact);
        
        if ($include_metadata) {
            $export_data = array(
                'metadata' => array(
                    'export_date' => current_time('mysql'),
                    'wordpress_version' => get_bloginfo('version'),
                    'plugin_version' => $this->version,
                    'site_url' => get_site_url(),
                    'redacted' => $should_redact
                ),
                'settings' => $data
            );
        } else {
            $export_data = $data;
        }
        
        $filename = 'ai1wm-settings-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Handle settings import
     */
    private function handle_import_settings() {
        if (empty($_FILES['json_file']['tmp_name'])) {
            $this->add_admin_notice('No file selected.', 'error');
            return;
        }
        
        // Validate file type
        $file_info = pathinfo($_FILES['json_file']['name']);
        if (strtolower($file_info['extension']) !== 'json') {
            $this->add_admin_notice('Please upload a JSON file.', 'error');
            return;
        }
        
        // Validate file size (max 10MB)
        if ($_FILES['json_file']['size'] > 10 * 1024 * 1024) {
            $this->add_admin_notice('File too large. Maximum size is 10MB.', 'error');
            return;
        }
        
        $json_data = file_get_contents($_FILES['json_file']['tmp_name']);
        if ($json_data === false) {
            $this->add_admin_notice('Failed to read file.', 'error');
            return;
        }
        
        $import_data = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->add_admin_notice('Invalid JSON file: ' . json_last_error_msg(), 'error');
            return;
        }
        
        // Handle different export formats
        if (isset($import_data['settings'])) {
            $settings_data = $import_data['settings'];
            $metadata = $import_data['metadata'] ?? array();
        } else {
            $settings_data = $import_data;
            $metadata = array();
        }
        
        if (!is_array($settings_data)) {
            $this->add_admin_notice('Invalid settings data format.', 'error');
            return;
        }
        
        // Create backup before import
        $this->create_settings_backup();
        
        $imported_count = 0;
        $skipped_count = 0;
        
        foreach ($settings_data as $option_name => $option_value) {
            if (!is_string($option_name) || strpos($option_name, 'ai1wm') === false) {
                $skipped_count++;
                continue;
            }
            
            if ($this->is_dangerous_option($option_name) || 
                $this->is_extension_versioning_option($option_name) ||
                strpos($option_name, 'ai1wm_inspector_backup_') === 0 ||
                strpos($option_name, 'ai1wm_manager_backup_') === 0 ||
                strpos($option_name, '_backup_') !== false) {
                $skipped_count++;
                continue;
            }
            
            if (update_option($option_name, $option_value)) {
                $imported_count++;
            }
        }
        
        $message = sprintf(
            'Import completed: %d settings imported, %d skipped.',
            $imported_count,
            $skipped_count
        );
        
        if (!empty($metadata['site_url']) && $metadata['site_url'] !== get_site_url()) {
            $message .= ' <strong>Warning:</strong> This backup was created on a different site (' . esc_html($metadata['site_url']) . ').';
        }
        
        $this->add_admin_notice($message, 'success');
    }

    /**
     * Handle settings backup
     */
    private function handle_backup_settings() {
        $data = $this->get_ai1wm_settings(false);
        
        $backup_data = array(
            'metadata' => array(
                'backup_date' => current_time('mysql'),
                'wordpress_version' => get_bloginfo('version'),
                'plugin_version' => $this->version,
                'site_url' => get_site_url(),
                'type' => 'automatic_backup'
            ),
            'settings' => $data
        );
        
        $filename = 'ai1wm-backup-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        echo json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Create settings backup
     */
    private function create_settings_backup() {
        $data = $this->get_ai1wm_settings(false);
        $backup_key = 'ai1wm_manager_settings_backup_' . time();
        update_option($backup_key, $data);
        $this->cleanup_old_settings_backups();
    }

    /**
     * Cleanup old settings backups
     */
    private function cleanup_old_settings_backups() {
        global $wpdb;
        
        $backups = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'ai1wm_manager_settings_backup_%' 
             OR option_name LIKE 'ai1wm_inspector_backup_%'
             ORDER BY option_name DESC",
            ARRAY_A
        );
        
        if (count($backups) > 5) {
            $to_delete = array_slice($backups, 5);
            foreach ($to_delete as $backup) {
                delete_option($backup['option_name']);
            }
        }
    }

    /**
     * Get AI1WM settings
     */
    private function get_ai1wm_settings($should_redact = false) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND option_name NOT LIKE %s 
                 AND option_name NOT LIKE %s
                 AND option_name NOT LIKE %s
                 AND option_name NOT LIKE %s",
                '%ai1wm%',
                'ai1wm_inspector_backup_%',
                'ai1wm_manager_backup_%',
                'ai1wm_manager_settings_backup_%',
                'ai1wm_manager_cleaned_duplicates'
            ),
            ARRAY_A
        );
        
        $data = array();
        foreach ($results as $row) {
            $value = maybe_unserialize($row['option_value']);
            
            // Skip backup-related options
            if (strpos($row['option_name'], '_backup_') !== false) {
                continue;
            }
            
            // Skip extension versioning and plugin management options
            if ($this->is_extension_versioning_option($row['option_name'])) {
                continue;
            }
            
            // Skip dangerous/site-specific options from export and display
            if ($this->is_dangerous_option($row['option_name'])) {
                continue;
            }
            
            // Clean up bloated ai1wm_updater data - keep it minimal like correct-one.json
            if ($row['option_name'] === 'ai1wm_updater' && is_array($value) && !empty($value)) {
                // Check if it contains detailed extension data (bloated format)
                $first_key = array_key_first($value);
                if ($first_key && is_array($value[$first_key]) && isset($value[$first_key]['name'])) {
                    // This is bloated format with detailed extension info, clean it up
                    $value = array(); // Keep it as empty array like correct-one.json
                }
            }
            
            if ($should_redact) {
                $value = $this->redact_sensitive_data($value);
            }
            $data[$row['option_name']] = $value;
        }
        
        return $data;
    }

    /**
     * Redact sensitive data
     */
    private function redact_sensitive_data($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = $this->redact_sensitive_data($value);
                } elseif (is_string($key) && $this->is_sensitive_key($key)) {
                    $data[$key] = '[REDACTED]';
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data->$key = $this->redact_sensitive_data($value);
                } elseif (is_string($key) && $this->is_sensitive_key($key)) {
                    $data->$key = '[REDACTED]';
                }
            }
        }
        
        return $data;
    }

    /**
     * Check if key is sensitive
     */
    private function is_sensitive_key($key) {
        $sensitive_patterns = array(
            'key', 'token', 'secret', 'password', 'pass', 'pwd',
            'access', 'auth', 'credential', 'api_key', 'private',
            'license', 'activation', 'signature', 'hash', 'hostname',
            'username', 'authentication'
        );
        
        $key_lower = strtolower($key);
        foreach ($sensitive_patterns as $pattern) {
            if (strpos($key_lower, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if option is related to extension versioning
     */
    private function is_extension_versioning_option($option_name) {
        $versioning_patterns = array(
            'ai1wm_manager_backup',
            'ai1wm_manager_cleaned_duplicates',
            'ai1wm_complete_manager_backup', 
            'ai1wm_complete_manager_cleaned_duplicates',
            'ai1wm_settings_manager_cleaned_duplicates'
        );
        
        foreach ($versioning_patterns as $pattern) {
            if (strpos($option_name, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if option is dangerous
     */
    private function is_dangerous_option($option_name) {
        $dangerous_patterns = array(
            'user_', 'admin_', 'active_plugins', 'template',
            'stylesheet', 'home', 'siteurl'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (strpos($option_name, $pattern) !== false) {
                return true;
            }
        }
        
        // Site-specific options that shouldn't be imported
        $site_specific_options = array(
            'ai1wm_backups_path',           // Server-specific backup path
            'ai1wm_backups_labels',         // Site-specific backup file references
            'ai1wmfe_ftp_timestamp',        // Last backup timestamp
            'ai1wm_secret_key',             // Site-specific security key
            'ai1wmue_eula_accepted_by',     // User ID from source site
            '_site_transient_ai1wm_last_check_for_updates' // Update check timestamp
        );
        
        if (in_array($option_name, $site_specific_options)) {
            return true;
        }
        
        return false;
    }

    /**
     * Add admin notice
     */
    private function add_admin_notice($message, $type = 'info') {
        add_action('admin_notices', function() use ($message, $type) {
            printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', 
                   esc_attr($type), wp_kses_post($message));
        });
    }

    /**
     * Get all backups (both extension and settings backups)
     */
    private function get_all_backups() {
        global $wpdb;
        
        $backups = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'ai1wm_manager_backup_%' 
             OR option_name LIKE 'ai1wm_manager_settings_backup_%'
             ORDER BY option_name DESC",
            ARRAY_A
        );
        
        $formatted_backups = array();
        foreach ($backups as $backup) {
            // Extract timestamp from option name
            if (preg_match('/(\d+)$/', $backup['option_name'], $matches)) {
                $timestamp = $matches[1];
                $type = (strpos($backup['option_name'], 'settings_backup') !== false) ? 'Settings Backup' : 'Extension Backup';
                
                $formatted_backups[] = array(
                    'option_name' => $backup['option_name'],
                    'timestamp' => $timestamp,
                    'type' => $type,
                    'data' => $backup['option_value']
                );
            }
        }
        
        return $formatted_backups;
    }

    /**
     * Handle individual backup removal
     */
    public function handle_remove_backup() {
        if (!isset($_POST['backup_key'])) {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=overview&backup_error=no_key'));
            exit;
        }
        
        $backup_key = sanitize_text_field($_POST['backup_key']);
        
        // Verify the backup exists and belongs to our plugin
        if (strpos($backup_key, 'ai1wm_manager_') !== 0) {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=overview&backup_error=invalid_key'));
            exit;
        }
        
        if (delete_option($backup_key)) {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=overview&backup_removed=success'));
        } else {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=overview&backup_error=delete_failed'));
        }
        exit;
    }

    /**
     * Handle removal of all backups (Overview tab - both types)
     */
    public function handle_remove_all_backups() {
        global $wpdb;
        
        $backup_options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'ai1wm_manager_backup_%' 
             OR option_name LIKE 'ai1wm_manager_settings_backup_%'",
            ARRAY_A
        );
        
        $removed_count = 0;
        foreach ($backup_options as $option) {
            if (delete_option($option['option_name'])) {
                $removed_count++;
            }
        }
        
        if ($removed_count > 0) {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=overview&all_backups_removed=success&count=' . $removed_count));
        } else {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=overview&backup_error=no_backups'));
        }
        exit;
    }

    /**
     * Get extension backups only
     */
    private function get_extension_backups() {
        global $wpdb;
        
        $backups = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'ai1wm_manager_backup_%' 
             AND option_name NOT LIKE 'ai1wm_manager_settings_backup_%'
             ORDER BY option_name DESC",
            ARRAY_A
        );
        
        $formatted_backups = array();
        foreach ($backups as $backup) {
            // Extract timestamp from option name
            if (preg_match('/(\d+)$/', $backup['option_name'], $matches)) {
                $timestamp = $matches[1];
                $backup_data = maybe_unserialize($backup['option_value']);
                $versions_count = 0;
                
                // Count versions in backup
                if (is_array($backup_data) && isset($backup_data['versions'])) {
                    $versions_count = count($backup_data['versions']);
                }
                
                $formatted_backups[] = array(
                    'option_name' => $backup['option_name'],
                    'timestamp' => $timestamp,
                    'type' => 'Extension Backup',
                    'versions_count' => $versions_count,
                    'data' => $backup['option_value']
                );
            }
        }
        
        return $formatted_backups;
    }

    /**
     * Handle individual extension backup removal
     */
    public function handle_remove_extension_backup() {
        if (!isset($_POST['backup_key'])) {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&extension_backup_error=no_key'));
            exit;
        }
        
        $backup_key = sanitize_text_field($_POST['backup_key']);
        
        // Verify the backup exists and belongs to our plugin (and is extension backup)
        if (strpos($backup_key, 'ai1wm_manager_backup_') !== 0 || 
            strpos($backup_key, 'ai1wm_manager_settings_backup_') === 0) {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&extension_backup_error=invalid_key'));
            exit;
        }
        
        if (delete_option($backup_key)) {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&extension_backup_removed=success'));
        } else {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&extension_backup_error=delete_failed'));
        }
        exit;
    }

    /**
     * Handle removal of all extension backups
     */
    public function handle_remove_all_extension_backups() {
        global $wpdb;
        
        $backup_options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'ai1wm_manager_backup_%' 
             AND option_name NOT LIKE 'ai1wm_manager_settings_backup_%'",
            ARRAY_A
        );
        
        $removed_count = 0;
        foreach ($backup_options as $option) {
            if (delete_option($option['option_name'])) {
                $removed_count++;
            }
        }
        
        if ($removed_count > 0) {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&all_extension_backups_removed=success&count=' . $removed_count));
        } else {
            wp_redirect(admin_url('tools.php?page=' . $this->plugin_slug . '&tab=extensions&extension_backup_error=no_backups'));
        }
        exit;
    }
}

// Initialize the plugin
new AI1WM_Manager();