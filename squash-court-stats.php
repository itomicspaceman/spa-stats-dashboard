<?php
/**
 * Plugin Name: Squash Court Stats
 * Plugin URI: https://stats.squashplayers.app
 * Description: Embeds dashboards and reports from stats.squashplayers.app into WordPress using shortcode [squash_court_stats]. Use dashboard="name" for dashboards or report="name" for trivia reports.
 * Version: 1.6.2
 * Author: Itomic Apps
 * Author URI: https://www.itomic.com.au
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/itomic/squash-court-stats
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load the updater class (only for self-hosted installations, not WordPress.org)
// WordPress.org plugins must use the built-in update system
if (file_exists(plugin_dir_path(__FILE__) . 'includes/class-plugin-updater.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-plugin-updater.php';
}

// Load the admin settings page
if (is_admin() && file_exists(plugin_dir_path(__FILE__) . 'includes/class-admin-settings.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-admin-settings.php';
}

class Squash_Stats_Dashboard {
    
    private $dashboard_url = 'https://stats.squashplayers.app';
    private $api_url = 'https://stats.squashplayers.app/squash';
    
    public function __construct() {
        // Register shortcode
        add_shortcode('squash_court_stats', array($this, 'render_dashboard_shortcode'));
        
        // Add CSS for full-width iframe
        add_action('wp_head', array($this, 'add_dashboard_styles'), 999);
        
        // Add admin help tabs
        add_action('admin_head', array($this, 'add_help_tabs'));
        
        // Add admin menu page for Settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add Settings link to plugin action links (appears before Deactivate)
        // Note: plugin_basename(__FILE__) returns 'squash-court-stats/squash-court-stats.php' when installed
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'), 10, 1);
        
        // Add "Check for updates" link in plugin row meta (appears below description)
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
        
        // Handle "Check for updates" action
        add_action('admin_init', array($this, 'handle_check_updates'));
        
        // Show success message on Updates page after checking
        add_action('admin_notices', array($this, 'show_update_check_notice'));
    }
    
    /**
     * Add CSS to make the dashboard iframe full-width
     * This breaks the iframe out of WordPress content containers
     */
    public function add_dashboard_styles() {
        // Try multiple methods to get the current post
        global $post;
        
        // Method 1: Use global $post if available
        if (empty($post) || !is_a($post, 'WP_Post')) {
            // Method 2: Try get_queried_object() for singular pages
            if (is_singular()) {
                $queried = get_queried_object();
                if (is_a($queried, 'WP_Post')) {
                    $post = $queried;
                }
            }
        }
        
        // Only add styles if we have a valid post with content
        if (!isset($post) || !is_a($post, 'WP_Post') || empty($post->post_content)) {
            return; // Exit early if no valid post
        }
        
        // Check if shortcode is present (safely)
        // Use function_exists check and wrap in try-catch for extra safety
        if (function_exists('has_shortcode')) {
            try {
                if (has_shortcode($post->post_content, 'squash_court_stats')) {
                    ?>
                    <style>
                /* Responsive dashboard container - works within any WordPress container */
                .squash-dashboard-wrapper {
                    width: 100%;
                    max-width: 100%;
                    margin: 0;
                    padding: 0;
                    position: relative;
                    overflow: hidden;
                    clear: both;
                }
                
                /* Ensure iframe is responsive and fills container */
                .squash-dashboard-iframe {
                    width: 100%;
                    max-width: 100%;
                    display: block;
                    margin: 0;
                    padding: 0;
                    border: 0;
                    vertical-align: top;
                }
                
                /* Full-width option - breaks out of containers when needed */
                .squash-dashboard-wrapper.full-width {
                    width: 100vw;
                    max-width: 100vw;
                    position: relative;
                    left: 50%;
                    right: 50%;
                    margin-left: -50vw;
                    margin-right: -50vw;
                }
                
                /* Compatibility fixes for common page builders */
                .wp-block-group .squash-dashboard-wrapper,
                .elementor-widget-container .squash-dashboard-wrapper,
                .vc_row .squash-dashboard-wrapper,
                .wpb_content_element .squash-dashboard-wrapper {
                    width: 100%;
                    max-width: 100%;
                }
                    </style>
                    <?php
                }
            } catch (Exception $e) {
                // Silently fail - don't break the page if shortcode check fails
                error_log('Squash Court Stats: Error checking for shortcode: ' . $e->getMessage());
            } catch (Error $e) {
                // Catch fatal errors too
                error_log('Squash Court Stats: Fatal error checking for shortcode: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Render the dashboard shortcode using iframe
     * 
     * This approach provides complete isolation between the dashboard and WordPress:
     * - No JavaScript conflicts
     * - No CSS conflicts
     * - No global variable pollution
     * - Geolocation works properly
     * - Uses postMessage API for dynamic height adjustment (no scrollbars)
     */
    public function render_dashboard_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'dashboard' => '',  // Dashboard name (e.g., 'world', 'country', 'venue-types')
            'report' => '',     // Report/trivia section name (e.g., 'graveyard', 'high-altitude')
            'charts' => '',     // Comma-separated chart IDs (e.g., 'venue-map,top-venues')
            'filter' => '',     // Geographic filter (e.g., 'country:AU', 'region:19', 'continent:5')
            'title' => '',      // Custom title override for charts/map
            'class' => '',      // Allow custom CSS classes
            'fullwidth' => '',  // Set to 'true' or '1' to enable full-width breakout
        ), $atts);
        
        // Build the iframe URL
        $url = $this->dashboard_url;
        $query_params = array();
        
        // Handle report parameter (trivia sections)
        if (!empty($atts['report'])) {
            $url .= '/trivia';
            $query_params['section'] = $atts['report'];
            // Individual reports: embed=1 will hide header AND sidebar (handled by Laravel)
        }
        // Handle dashboard parameter
        elseif (!empty($atts['dashboard'])) {
            // Special case: dashboard="trivia" goes to full trivia page
            if ($atts['dashboard'] === 'trivia') {
                $url .= '/trivia';
                // No section parameter = show full trivia page with sidebar
                // Laravel will show sidebar when embed=1 but no section is specified
            } elseif ($atts['dashboard'] === 'world') {
                // World dashboard: go directly to root route (no redirect needed)
                // Root route already renders world dashboard
                // Don't add /render, just use root with query params
            } else {
                // Other dashboards: use /render route which will redirect appropriately
                $url .= '/render';
                $query_params['dashboard'] = $atts['dashboard'];
            }
        }
        // Handle charts parameter
        elseif (!empty($atts['charts'])) {
            $url .= '/render';
            $query_params['charts'] = $atts['charts'];
        }
        // If none specified, default to the world dashboard (root URL)
        
        // Add filter parameter if specified
        if (!empty($atts['filter'])) {
            $query_params['filter'] = $atts['filter'];
        }
        
        // Add title parameter if specified
        if (!empty($atts['title'])) {
            $query_params['title'] = $atts['title'];
        }
        
        // Always add embed parameter to hide header/navigation for ALL embedded content
        // Laravel trivia page will automatically show sidebar for full trivia (no section)
        // and hide sidebar for individual reports (with section parameter)
        $query_params['embed'] = '1';
        
        // Build query string
        if (!empty($query_params)) {
            $url .= '?' . http_build_query($query_params);
        }
        
        // Generate unique ID for this iframe instance
        $iframe_id = 'squash-dashboard-' . uniqid();
        
        // Determine if full-width should be enabled
        $fullwidth = !empty($atts['fullwidth']) && in_array(strtolower($atts['fullwidth']), array('true', '1', 'yes', 'on'));
        $wrapper_class = 'squash-dashboard-wrapper';
        if ($fullwidth) {
            $wrapper_class .= ' full-width';
        }
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . esc_attr($atts['class']);
        }
        
        // Wrap iframe in responsive container
        $html = '<div class="' . esc_attr($wrapper_class) . '">';
        
        // Build iframe HTML
        $html .= sprintf(
            '<iframe 
                id="%s"
                src="%s" 
                width="100%%" 
                style="border: none; display: block; overflow: hidden; min-height: 500px;"
                frameborder="0"
                scrolling="no"
                class="squash-dashboard-iframe"
                loading="lazy"
                sandbox="allow-scripts allow-same-origin allow-popups"
                title="Squash Court Stats">
            </iframe>',
            esc_attr($iframe_id),
            esc_url($url)
        );
        
        // Add postMessage listener for dynamic height adjustment
        $html .= sprintf(
            '<script>
            (function() {
                var iframe = document.getElementById("%s");
                
                // Listen for height messages from the iframe
                window.addEventListener("message", function(event) {
                    // Security: verify origin
                    if (event.origin !== "https://stats.squashplayers.app") {
                        return;
                    }
                    
                    // Check if this is a height update message
                    if (event.data && event.data.type === "squash-dashboard-height") {
                        iframe.style.height = event.data.height + "px";
                        console.log("Dashboard height updated:", event.data.height);
                    }
                });
                
                // Fallback: if no height message received after 5 seconds, set a default height
                setTimeout(function() {
                    if (iframe.style.height === "" || iframe.style.height === "500px") {
                        iframe.style.height = "3000px";
                        console.log("Dashboard height fallback applied");
                    }
                }, 5000);
            })();
            </script>',
            esc_js($iframe_id)
        );
        
        // Close wrapper div
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Add help tabs to plugin pages
     */
    public function add_help_tabs() {
        $screen = get_current_screen();
        
        // Only add help tabs on relevant pages
        if (!$screen || !in_array($screen->id, array('plugins', 'settings_page_squash-court-stats', 'post', 'page'))) {
            return;
        }
        
        // Quick Start tab
        $screen->add_help_tab(array(
            'id'      => 'squash-court-stats-quickstart',
            'title'   => __('Quick Start', 'squash-court-stats'),
            'content' => $this->get_quickstart_help_content(),
        ));
        
        // Shortcode Reference tab
        $screen->add_help_tab(array(
            'id'      => 'squash-court-stats-shortcodes',
            'title'   => __('Shortcode Reference', 'squash-court-stats'),
            'content' => $this->get_shortcode_help_content(),
        ));
        
        // Examples tab
        $screen->add_help_tab(array(
            'id'      => 'squash-court-stats-examples',
            'title'   => __('Examples', 'squash-court-stats'),
            'content' => $this->get_examples_help_content(),
        ));
        
        // Help sidebar
        $screen->set_help_sidebar($this->get_help_sidebar());
    }
    
    /**
     * Quick Start help content
     */
    private function get_quickstart_help_content() {
        return '<p><strong>' . __('Getting Started', 'squash-court-stats') . '</strong></p>' .
               '<ol>' .
               '<li>' . __('Activate the plugin', 'squash-court-stats') . '</li>' .
               '<li>' . __('Go to any page or post editor', 'squash-court-stats') . '</li>' .
               '<li>' . __('Add the shortcode: <code>[squash_court_stats]</code>', 'squash-court-stats') . '</li>' .
               '<li>' . __('Publish and view your page!', 'squash-court-stats') . '</li>' .
               '</ol>' .
               '<p><strong>' . __('Need More Options?', 'squash-court-stats') . '</strong></p>' .
               '<p>' . __('Use parameters to customize what displays:', 'squash-court-stats') . '</p>' .
               '<ul>' .
               '<li><code>dashboard="world"</code> - ' . __('Full dashboard', 'squash-court-stats') . '</li>' .
               '<li><code>report="graveyard"</code> - ' . __('Specific report section', 'squash-court-stats') . '</li>' .
               '<li><code>charts="venue-map"</code> - ' . __('Individual charts', 'squash-court-stats') . '</li>' .
               '</ul>';
    }
    
    /**
     * Shortcode Reference help content
     */
    private function get_shortcode_help_content() {
        return '<p><strong>' . __('Shortcode Syntax', 'squash-court-stats') . '</strong></p>' .
               '<p><code>[squash_court_stats]</code></p>' .
               '<p>' . __('Base shortcode. Displays the default world dashboard.', 'squash-court-stats') . '</p>' .
               
               '<p><strong>' . __('Dashboards', 'squash-court-stats') . '</strong></p>' .
               '<ul>' .
               '<li><code>[squash_court_stats dashboard="world"]</code> - ' . __('World statistics dashboard', 'squash-court-stats') . '</li>' .
               '<li><code>[squash_court_stats dashboard="country"]</code> - ' . __('Country statistics dashboard', 'squash-court-stats') . '</li>' .
               '<li><code>[squash_court_stats dashboard="venue-types"]</code> - ' . __('Venue types dashboard', 'squash-court-stats') . '</li>' .
               '<li><code>[squash_court_stats dashboard="trivia"]</code> - ' . __('Full trivia page with all trivia sections', 'squash-court-stats') . '</li>' .
               '</ul>' .
               
               '<p><strong>' . __('Reports', 'squash-court-stats') . '</strong></p>' .
               '<ul>' .
               '<li><code>[squash_court_stats report="graveyard"]</code> - ' . __('Squash court graveyard', 'squash-court-stats') . '</li>' .
               '<li><code>[squash_court_stats report="high-altitude"]</code> - ' . __('High altitude venues', 'squash-court-stats') . '</li>' .
               '<li><code>[squash_court_stats report="loneliest"]</code> - ' . __('Loneliest courts', 'squash-court-stats') . '</li>' .
               '<li><code>[squash_court_stats report="word-cloud"]</code> - ' . __('Countries word cloud', 'squash-court-stats') . '</li>' .
               '</ul>' .
               
               '<p><strong>' . __('Individual Charts', 'squash-court-stats') . '</strong></p>' .
               '<ul>' .
               '<li><code>[squash_court_stats charts="venue-map"]</code> - ' . __('Just the map', 'squash-court-stats') . '</li>' .
               '<li><code>[squash_court_stats charts="summary-stats,top-venues"]</code> - ' . __('Multiple charts', 'squash-court-stats') . '</li>' .
               '</ul>' .
               
               '<p><strong>' . __('Additional Parameters', 'squash-court-stats') . '</strong></p>' .
               '<ul>' .
               '<li><code>filter="country:AU"</code> - ' . __('Geographic filter', 'squash-court-stats') . '</li>' .
               '<li><code>class="my-custom-class"</code> - ' . __('Custom CSS class', 'squash-court-stats') . '</li>' .
               '<li><code>fullwidth="true"</code> - ' . __('Enable full-width display (breaks out of container)', 'squash-court-stats') . '</li>' .
               '</ul>';
    }
    
    /**
     * Examples help content
     */
    private function get_examples_help_content() {
        return '<p><strong>' . __('Common Examples', 'squash-court-stats') . '</strong></p>' .
               
               '<p><strong>' . __('1. Default Dashboard', 'squash-court-stats') . '</strong></p>' .
               '<p><code>[squash_court_stats]</code></p>' .
               '<p>' . __('Shows the complete world statistics dashboard.', 'squash-court-stats') . '</p>' .
               
               '<p><strong>' . __('2. Specific Report', 'squash-court-stats') . '</strong></p>' .
               '<p><code>[squash_court_stats report="graveyard"]</code></p>' .
               '<p>' . __('Shows only the squash court graveyard report.', 'squash-court-stats') . '</p>' .
               
               '<p><strong>' . __('3. Custom Chart Combination', 'squash-court-stats') . '</strong></p>' .
               '<p><code>[squash_court_stats charts="venue-map,summary-stats,top-venues"]</code></p>' .
               '<p>' . __('Shows only the selected charts.', 'squash-court-stats') . '</p>' .
               
               '<p><strong>' . __('4. With Custom Styling', 'squash-court-stats') . '</strong></p>' .
               '<p><code>[squash_court_stats dashboard="world" class="full-width-stats"]</code></p>' .
               '<p>' . __('Adds a custom CSS class for styling.', 'squash-court-stats') . '</p>' .
               
               '<p><strong>' . __('View All Options', 'squash-court-stats') . '</strong></p>' .
               '<p><a href="https://stats.squashplayers.app/charts" target="_blank">' . __('Browse Chart Gallery', 'squash-court-stats') . '</a></p>';
    }
    
    /**
     * Help sidebar content
     */
    private function get_help_sidebar() {
        return '<p><strong>' . __('For more information:', 'squash-court-stats') . '</strong></p>' .
               '<p><a href="https://stats.squashplayers.app/charts" target="_blank">' . __('Chart Gallery', 'squash-court-stats') . '</a></p>' .
               '<p><a href="https://github.com/itomic/squash-court-stats" target="_blank">' . __('GitHub Repository', 'squash-court-stats') . '</a></p>' .
               '<p><a href="https://www.itomic.com.au" target="_blank">' . __('Itomic Apps', 'squash-court-stats') . '</a></p>';
    }
    
    /**
     * Add admin menu page for Settings
     */
    public function add_admin_menu() {
        add_options_page(
            __('Squash Court Stats Settings', 'squash-court-stats'),
            __('Squash Court Stats', 'squash-court-stats'),
            'manage_options',
            'squash-court-stats',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php
            // Show success message if update check was just performed
            if (isset($_GET['squash-update-checked']) && $_GET['squash-update-checked'] == '1') {
                echo '<div class="notice notice-success is-dismissible"><p>';
                _e('Update check completed. If an update is available, you will see it on the Plugins page or Updates page.', 'squash-court-stats');
                echo '</p></div>';
            }
            ?>
            
            <div class="squash-court-stats-settings">
                <div class="card" style="max-width: 800px;">
                    <h2><?php _e('Plugin Updates', 'squash-court-stats'); ?></h2>
                    <p><?php _e('This plugin automatically checks for updates every 12 hours. You can also check for updates manually:', 'squash-court-stats'); ?></p>
                    <?php
                    $check_url = wp_nonce_url(
                        admin_url('plugins.php?squash-court-stats-check-updates=1'),
                        'squash-court-stats-check-updates'
                    );
                    ?>
                    <p>
                        <a href="<?php echo esc_url($check_url); ?>" class="button button-primary">
                            <?php _e('Check for updates now', 'squash-court-stats'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('update-core.php')); ?>" class="button">
                            <?php _e('View all updates', 'squash-court-stats'); ?>
                        </a>
                    </p>
                    <p class="description">
                        <?php _e('This will clear the update cache and force WordPress to check GitHub for the latest release.', 'squash-court-stats'); ?>
                    </p>
                </div>
                
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('Quick Start', 'squash-court-stats'); ?></h2>
                    <ol>
                        <li><?php _e('Go to any page or post editor', 'squash-court-stats'); ?></li>
                        <li><?php _e('Add the shortcode:', 'squash-court-stats'); ?> <code>[squash_court_stats]</code></li>
                        <li><?php _e('Publish and view your page!', 'squash-court-stats'); ?></li>
                    </ol>
                </div>
                
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('Shortcode Reference', 'squash-court-stats'); ?></h2>
                    
                    <h3><?php _e('Basic Usage', 'squash-court-stats'); ?></h3>
                    <p><code>[squash_court_stats]</code></p>
                    <p><?php _e('Displays the default world dashboard.', 'squash-court-stats'); ?></p>
                    
                    <h3><?php _e('Dashboards', 'squash-court-stats'); ?></h3>
                    <ul>
                        <li><code>[squash_court_stats dashboard="world"]</code> - <?php _e('World statistics dashboard', 'squash-court-stats'); ?></li>
                        <li><code>[squash_court_stats dashboard="country"]</code> - <?php _e('Country statistics dashboard', 'squash-court-stats'); ?></li>
                        <li><code>[squash_court_stats dashboard="venue-types"]</code> - <?php _e('Venue types dashboard', 'squash-court-stats'); ?></li>
                        <li><code>[squash_court_stats dashboard="trivia"]</code> - <?php _e('Full trivia page with all trivia sections', 'squash-court-stats'); ?></li>
                    </ul>
                    
                    <h3><?php _e('Reports', 'squash-court-stats'); ?></h3>
                    <ul>
                        <li><code>[squash_court_stats report="graveyard"]</code> - <?php _e('Squash court graveyard', 'squash-court-stats'); ?></li>
                        <li><code>[squash_court_stats report="high-altitude"]</code> - <?php _e('High altitude venues', 'squash-court-stats'); ?></li>
                        <li><code>[squash_court_stats report="loneliest"]</code> - <?php _e('Loneliest courts', 'squash-court-stats'); ?></li>
                        <li><code>[squash_court_stats report="word-cloud"]</code> - <?php _e('Countries word cloud', 'squash-court-stats'); ?></li>
                    </ul>
                    
                    <h3><?php _e('Individual Charts', 'squash-court-stats'); ?></h3>
                    <ul>
                        <li><code>[squash_court_stats charts="venue-map"]</code> - <?php _e('Just the map', 'squash-court-stats'); ?></li>
                        <li><code>[squash_court_stats charts="summary-stats,top-venues"]</code> - <?php _e('Multiple charts', 'squash-court-stats'); ?></li>
                    </ul>
                    
                    <h3><?php _e('Additional Parameters', 'squash-court-stats'); ?></h3>
                    <ul>
                        <li><code>filter="country:AU"</code> - <?php _e('Geographic filter', 'squash-court-stats'); ?></li>
                        <li><code>class="my-custom-class"</code> - <?php _e('Custom CSS class', 'squash-court-stats'); ?></li>
                        <li><code>fullwidth="true"</code> - <?php _e('Enable full-width display (breaks out of container)', 'squash-court-stats'); ?></li>
                    </ul>
                </div>
                
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('Examples', 'squash-court-stats'); ?></h2>
                    
                    <h3><?php _e('1. Default Dashboard', 'squash-court-stats'); ?></h3>
                    <p><code>[squash_court_stats dashboard="world"]</code></p>
                    <p><?php _e('Shows the full world statistics dashboard.', 'squash-court-stats'); ?></p>
                    
                    <h3><?php _e('2. Specific Report', 'squash-court-stats'); ?></h3>
                    <p><code>[squash_court_stats report="graveyard"]</code></p>
                    <p><?php _e('Shows only the squash court graveyard report.', 'squash-court-stats'); ?></p>
                    
                    <h3><?php _e('3. Custom Chart Combination', 'squash-court-stats'); ?></h3>
                    <p><code>[squash_court_stats charts="venue-map,summary-stats,top-venues"]</code></p>
                    <p><?php _e('Shows only the selected charts.', 'squash-court-stats'); ?></p>
                    
                    <h3><?php _e('4. With Custom Styling', 'squash-court-stats'); ?></h3>
                    <p><code>[squash_court_stats dashboard="world" class="my-custom-class"]</code></p>
                    <p><?php _e('Adds a custom CSS class for styling.', 'squash-court-stats'); ?></p>
                    
                    <h3><?php _e('5. Full-Width Display', 'squash-court-stats'); ?></h3>
                    <p><code>[squash_court_stats dashboard="world" fullwidth="true"]</code></p>
                    <p><?php _e('Breaks out of the content container to display full-width across the page.', 'squash-court-stats'); ?></p>
                </div>
                
                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2><?php _e('More Information', 'squash-court-stats'); ?></h2>
                    <ul>
                        <li><a href="https://stats.squashplayers.app/charts" target="_blank"><?php _e('Browse Chart Gallery', 'squash-court-stats'); ?></a></li>
                        <li><a href="https://github.com/itomic/squash-court-stats" target="_blank"><?php _e('GitHub Repository', 'squash-court-stats'); ?></a></li>
                        <li><a href="https://www.itomic.com.au" target="_blank"><?php _e('Itomic Apps', 'squash-court-stats'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add Settings link to plugin action links
     * This adds a "Settings" link before "Deactivate" (standard WordPress convention)
     */
    public function add_plugin_action_links($links) {
        // Link to the settings page
        $settings_url = admin_url('options-general.php?page=squash-court-stats');
        $settings_link = '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'squash-court-stats') . '</a>';
        
        // Add it at the beginning of the links array
        array_unshift($links, $settings_link);
        
        return $links;
    }
    
    /**
     * Add "Check for updates" link in plugin row meta
     * This appears below the plugin description on the Plugins page
     * 
     * @param array $links Existing meta links
     * @param string $file Plugin file
     * @return array Modified links
     */
    public function add_plugin_row_meta($links, $file) {
        // Only add to our plugin
        if ($file !== plugin_basename(__FILE__)) {
            return $links;
        }
        
        // Add "Check for updates" link
        $check_url = wp_nonce_url(
            admin_url('plugins.php?squash-court-stats-check-updates=1'),
            'squash-court-stats-check-updates'
        );
        $check_link = '<a href="' . esc_url($check_url) . '">' . __('Check for updates', 'squash-court-stats') . '</a>';
        
        // Add after existing links
        $links[] = $check_link;
        
        return $links;
    }
    
    /**
     * Handle "Check for updates" action
     * Clears update cache and forces WordPress to check for updates
     */
    public function handle_check_updates() {
        // Check if this is our update check request
        if (!isset($_GET['squash-court-stats-check-updates']) || !isset($_GET['_wpnonce'])) {
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_GET['_wpnonce'], 'squash-court-stats-check-updates')) {
            wp_die(__('Security check failed', 'squash-court-stats'));
        }
        
        // Check user permissions
        if (!current_user_can('update_plugins')) {
            wp_die(__('You do not have permission to update plugins', 'squash-court-stats'));
        }
        
        // Clear update cache for our plugin
        $cache_key = 'squash_dashboard_update_' . md5('itomic/squash-court-stats');
        delete_transient($cache_key);
        
        // Clear WordPress update transients
        delete_site_transient('update_plugins');
        
        // Force WordPress to check for updates
        wp_update_plugins();
        
        // Redirect to Updates page with success message
        $redirect_url = add_query_arg(
            'squash-update-checked',
            '1',
            admin_url('update-core.php')
        );
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Show success notice on Updates page after checking for updates
     */
    public function show_update_check_notice() {
        // Only show on Updates page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'update-core') {
            return;
        }
        
        // Check if update check was just performed
        if (isset($_GET['squash-update-checked']) && $_GET['squash-update-checked'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>';
            _e('Update check completed for Squash Court Stats. If an update is available, it will appear in the list below.', 'squash-court-stats');
            echo '</p></div>';
        }
    }
    
    
}

// Initialize the plugin
new Squash_Stats_Dashboard();

// Initialize the updater (only for self-hosted installations, NOT for WordPress.org)
// WordPress.org plugins use the built-in update system and should not include this file
// The Update URI header tells WordPress this is a self-hosted plugin
if (is_admin() && class_exists('Squash_Stats_Dashboard_Updater')) {
    // Only initialize updater if Update URI is set (indicates self-hosted)
    // This prevents warnings in Plugin Check tool for WordPress.org submissions
    $plugin_data = get_file_data(__FILE__, array('UpdateURI' => 'Update URI'));
    
    // If Update URI is set and points to GitHub (not wordpress.org), use custom updater
    if (!empty($plugin_data['UpdateURI']) && strpos($plugin_data['UpdateURI'], 'wordpress.org') === false) {
        $updater = new Squash_Stats_Dashboard_Updater(
            plugin_basename(__FILE__),
            'itomic/squash-court-stats'
        );
        
        // Opt into WordPress auto-updates (WordPress 5.5+)
        // This allows users to enable auto-updates from the Plugins page
        add_filter('auto_update_plugin', function($update, $item) {
            // Only auto-update this specific plugin
            if (isset($item->plugin) && $item->plugin === plugin_basename(__FILE__)) {
                return true; // Allow auto-updates (user can still disable per-plugin)
            }
            return $update;
        }, 10, 2);
    }
}

