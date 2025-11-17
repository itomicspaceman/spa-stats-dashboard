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

