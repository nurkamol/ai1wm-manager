<?php
/**
 * Plugin Name: All-in-One WP Migration Manager
 * Plugin URI: https://github.com/nurkamol/ai1wm-manager
 * Description: Complete management solution for All-in-One WP Migration plugin - manage extension versions, export/import settings, and configure plugin options with enhanced security and backup features.
 * Version: 3.0.0
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
    private $version = '3.0.0';
    
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
        
        // Initialize hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_actions'));
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
        ');
    }

    /**
     * Handle form actions
     */
    public function handle_actions() {
        if (!isset($_POST['action']) || !isset($_POST['_wpnonce'])) {
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
        }
    }

    /**
     * Clean up backup duplicates
     */
    public function cleanup_backup_duplicates() {
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
        
        update_option('ai1wm_complete_manager_cleaned_duplicates', true);
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

        ?>
        <div class="wrap ai1wm-manager">
            <h1>🚀 All-in-One WP Migration Manager</h1>
            <p>Complete management solution for your All-in-One WP Migration plugin and extensions.</p>
            
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
                    </table>
                </div>
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
            </script>
        </div>
        <?php
    }

    /**
     * Render extensions management section
     */
    private function render_extensions_section($current_versions) {
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
            <p>Export your AI1WM settings to a JSON file for backup or transfer purposes.</p>
            
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
                <strong>⚠️ Warning:</strong> Importing settings will overwrite your current AI1WM configuration. 
                A backup will be created automatically before import.
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
            <p>Create a complete backup of your current AI1WM settings.</p>
            
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
            <p>Below are your current AI1WM settings. <strong>Note:</strong> Sensitive data is automatically redacted in this view for security.</p>
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
                 AND option_name NOT LIKE %s",
                '%ai1wm%',
                'ai1wm_inspector_backup_%',
                'ai1wm_manager_backup_%',
                'ai1wm_manager_settings_backup_%'
            ),
            ARRAY_A
        );
        
        $data = array();
        foreach ($results as $row) {
            $value = maybe_unserialize($row['option_value']);
            
            if (strpos($row['option_name'], '_backup_') !== false) {
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
}

// Initialize the plugin
new AI1WM_Manager();
        