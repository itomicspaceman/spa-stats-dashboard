<?php
/**
 * Admin Settings Page for Squash Stats Dashboard Plugin
 *
 * Provides a visual interface for:
 * - Browsing available dashboards
 * - Selecting individual charts
 * - Generating shortcodes
 * - Copying shortcodes to clipboard
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Squash_Stats_Dashboard_Admin {
    
    private $api_base = 'https://stats.squashplayers.app/api';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_head', array($this, 'add_help_tab'));
    }
    
    /**
     * Add admin menu item under Settings
     */
    public function add_admin_menu() {
        add_options_page(
            'Squash Stats Dashboard',
            'Squash Stats',
            'manage_options',
            'squash-stats-dashboard',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin assets (CSS and JS)
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our settings page
        if ($hook !== 'settings_page_squash-stats-dashboard') {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'squash-stats-admin',
            plugins_url('assets/admin/admin-styles.css', dirname(__FILE__)),
            array(),
            '1.4.0'
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'squash-stats-admin',
            plugins_url('assets/admin/admin-scripts.js', dirname(__FILE__)),
            array('jquery'),
            '1.4.0',
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('squash-stats-admin', 'squashStatsAdmin', array(
            'apiBase' => $this->api_base,
            'nonce' => wp_create_nonce('squash_stats_admin'),
        ));
    }
    
    /**
     * Add contextual help tab
     */
    public function add_help_tab() {
        $screen = get_current_screen();
        
        if ($screen->id !== 'settings_page_squash-stats-dashboard') {
            return;
        }
        
        $screen->add_help_tab(array(
            'id' => 'squash-stats-overview',
            'title' => 'Overview',
            'content' => '
                <h3>Squash Stats Dashboard</h3>
                <p>This plugin allows you to embed interactive squash statistics dashboards and charts on your WordPress site.</p>
                <h4>Quick Start:</h4>
                <ol>
                    <li>Choose a full dashboard or select individual charts</li>
                    <li>Click "Copy Shortcode"</li>
                    <li>Paste the shortcode into any page or post</li>
                </ol>
            '
        ));
        
        $screen->add_help_tab(array(
            'id' => 'squash-stats-examples',
            'title' => 'Shortcode Examples',
            'content' => '
                <h3>Shortcode Examples</h3>
                <h4>Full Dashboards:</h4>
                <pre>[squash_stats_dashboard]</pre>
                <p>Default world dashboard with all charts</p>
                
                <pre>[squash_stats_dashboard dashboard="country"]</pre>
                <p>Country-specific statistics</p>
                
                <pre>[squash_stats_dashboard dashboard="venue-types"]</pre>
                <p>Venue types and categories analysis</p>
                
                <h4>Individual Charts:</h4>
                <pre>[squash_stats_dashboard charts="venue-map"]</pre>
                <p>Just the interactive map</p>
                
                <pre>[squash_stats_dashboard charts="summary-stats,top-venues,top-courts"]</pre>
                <p>Multiple specific charts</p>
            '
        ));
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p class="description">
                Select a full dashboard or choose individual charts to embed on your site. 
                <a href="https://stats.squashplayers.app/charts" target="_blank">View live gallery →</a>
            </p>
            
            <div class="squash-stats-admin-container">
                <!-- Loading indicator -->
                <div id="squash-loading" class="squash-loading">
                    <span class="spinner is-active"></span>
                    <p>Loading charts and dashboards...</p>
                </div>
                
                <!-- Error message -->
                <div id="squash-error" class="notice notice-error" style="display: none;">
                    <p>Failed to load charts and dashboards. Please check your internet connection and try again.</p>
                </div>
                
                <!-- Tabs -->
                <nav class="nav-tab-wrapper" id="squash-tabs" style="display: none;">
                    <a href="#tab-dashboards" class="nav-tab nav-tab-active">Full Dashboards</a>
                    <a href="#tab-charts" class="nav-tab">Individual Charts</a>
                </nav>
                
                <!-- Tab: Full Dashboards -->
                <div id="tab-dashboards" class="squash-tab-content" style="display: none;">
                    <h2>Full Dashboards</h2>
                    <p>Select a complete dashboard with pre-configured charts:</p>
                    <div id="dashboards-grid" class="squash-grid"></div>
                </div>
                
                <!-- Tab: Individual Charts -->
                <div id="tab-charts" class="squash-tab-content" style="display: none;">
                    <h2>Individual Charts</h2>
                    <p>Select one or more charts to create a custom dashboard:</p>
                    
                    <!-- Category filter -->
                    <div class="squash-filter">
                        <label for="chart-category">Filter by category:</label>
                        <select id="chart-category">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    
                    <div id="charts-grid" class="squash-grid"></div>
                    
                    <!-- Selected charts summary -->
                    <div id="selected-summary" class="squash-selected-summary" style="display: none;">
                        <h3>Selected Charts (<span id="selected-count">0</span>)</h3>
                        <div id="selected-list"></div>
                        <button type="button" class="button" id="clear-selection">Clear Selection</button>
                    </div>
                </div>
                
                <!-- Shortcode output -->
                <div id="shortcode-output" class="squash-shortcode-output" style="display: none;">
                    <h3>Your Shortcode</h3>
                    
                    <!-- Optional parameters -->
                    <div class="squash-optional-params">
                        <h4>Optional Parameters</h4>
                        
                        <div class="squash-param-group">
                            <label for="shortcode-filter">
                                <strong>Geographic Filter:</strong>
                                <span class="description">Limit data to a specific area (e.g., country:AU, region:19, continent:5)</span>
                            </label>
                            <input type="text" id="shortcode-filter" class="regular-text" placeholder="e.g., country:AU">
                            <p class="description">
                                <a href="https://spa.test/geographic-areas" target="_blank">View all available area codes →</a>
                            </p>
                        </div>
                        
                        <div class="squash-param-group">
                            <label for="shortcode-title">
                                <strong>Custom Title:</strong>
                                <span class="description">Override the default chart/map titles</span>
                            </label>
                            <input type="text" id="shortcode-title" class="regular-text" placeholder="e.g., Australian Squash Venues">
                        </div>
                    </div>
                    
                    <div class="squash-shortcode-box">
                        <code id="generated-shortcode">[squash_stats_dashboard]</code>
                        <button type="button" class="button button-primary" id="copy-shortcode">
                            <span class="dashicons dashicons-clipboard"></span> Copy to Clipboard
                        </button>
                    </div>
                    <p class="description">
                        Copy this shortcode and paste it into any page or post where you want the dashboard to appear.
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the admin class
if (is_admin()) {
    new Squash_Stats_Dashboard_Admin();
}

