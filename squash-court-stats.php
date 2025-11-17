<?php
/**
 * Plugin Name: Squash Court Stats
 * Plugin URI: https://stats.squashplayers.app
 * Description: Embeds dashboards and reports from stats.squashplayers.app into WordPress using shortcode [squash_court_stats]. Use dashboard="name" for dashboards or report="name" for trivia reports.
 * Version: 1.5.0
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
        add_action('wp_head', array($this, 'add_dashboard_styles'));
        
        // Add admin help tabs
        add_action('admin_head', array($this, 'add_help_tabs'));
    }
    
    /**
     * Add CSS to make the dashboard iframe full-width
     * This breaks the iframe out of WordPress content containers
     */
    public function add_dashboard_styles() {
        global $post;
        
        // Only add styles if shortcode is present
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'squash_court_stats')) {
            ?>
            <style>
                /* Full-width dashboard container */
                .squash-dashboard-wrapper {
                    width: 100vw;
                    position: relative;
                    left: 50%;
                    right: 50%;
                    margin-left: -50vw;
                    margin-right: -50vw;
                    max-width: 100vw;
                }
                
                /* Ensure iframe is full width within wrapper */
                .squash-dashboard-iframe {
                    width: 100%;
                    display: block;
                }
            </style>
            <?php
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
        ), $atts);
        
        // Build the iframe URL
        $url = $this->dashboard_url;
        $query_params = array();
        
        // Handle report parameter (trivia sections)
        if (!empty($atts['report'])) {
            $url .= '/trivia';
            $query_params['section'] = $atts['report'];
        }
        // Handle dashboard parameter
        elseif (!empty($atts['dashboard'])) {
            $url .= '/render';
            $query_params['dashboard'] = $atts['dashboard'];
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
        
        // Add embed parameter to hide navigation/header
        $query_params['embed'] = '1';
        
        // Build query string
        if (!empty($query_params)) {
            $url .= '?' . http_build_query($query_params);
        } else {
            // If no query params, just add embed parameter
            $url .= '?embed=1';
        }
        
        // Generate unique ID for this iframe instance
        $iframe_id = 'squash-dashboard-' . uniqid();
        
        // Wrap iframe in full-width container
        $html = '<div class="squash-dashboard-wrapper">';
        
        // Build iframe HTML
        $html .= sprintf(
            '<iframe 
                id="%s"
                src="%s" 
                width="100%%" 
                style="border: none; display: block; overflow: hidden; min-height: 500px;"
                frameborder="0"
                scrolling="no"
                class="squash-dashboard-iframe %s"
                loading="lazy"
                sandbox="allow-scripts allow-same-origin allow-popups"
                title="Squash Court Stats">
            </iframe>',
            esc_attr($iframe_id),
            esc_url($url),
            esc_attr($atts['class'])
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
        if (!$screen || !in_array($screen->id, array('plugins', 'settings_page_squash-stats', 'post', 'page'))) {
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
    
    
}

// Initialize the plugin
new Squash_Stats_Dashboard();

// Initialize the updater (only if the class exists - for self-hosted installations)
// WordPress.org plugins use the built-in update system and should not include this file
if (is_admin() && class_exists('Squash_Stats_Dashboard_Updater')) {
    new Squash_Stats_Dashboard_Updater(
        plugin_basename(__FILE__),
        'itomic/squash-court-stats'
    );
}

