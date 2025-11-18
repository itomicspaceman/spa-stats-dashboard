<?php
/**
 * Plugin Updater Class
 * 
 * Handles automatic updates from GitHub releases
 * Checks for new versions and provides update notifications in WordPress
 * 
 * @package Squash_Stats_Dashboard
 * @version 1.5.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Squash_Stats_Dashboard_Updater {
    
    private $plugin_file;
    private $github_repo;
    private $plugin_slug;
    private $version;
    private $cache_key;
    private $cache_allowed;
    
    /**
     * Constructor
     * 
     * @param string $plugin_file Path to the main plugin file
     * @param string $github_repo GitHub repository in format 'username/repo'
     */
    public function __construct($plugin_file, $github_repo) {
        $this->plugin_file = $plugin_file;
        $this->github_repo = $github_repo;
        $this->plugin_slug = plugin_basename($plugin_file);
        
        // Get plugin version from main file
        $plugin_data = get_file_data(WP_PLUGIN_DIR . '/' . $this->plugin_slug, array('Version' => 'Version'));
        $this->version = $plugin_data['Version'];
        
        $this->cache_key = 'squash_dashboard_update_' . md5($this->github_repo);
        $this->cache_allowed = true;
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        // Add custom update message
        add_action('in_plugin_update_message-' . $this->plugin_slug, array($this, 'update_message'), 10, 2);
    }
    
    /**
     * Check for plugin updates
     * 
     * @param object $transient Update transient
     * @return object Modified transient
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version info
        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare($this->version, $remote_version->version, '<')) {
            // CRITICAL: The 'plugin' field MUST match the installed plugin's basename exactly
            // Format: 'plugin-dir/plugin-file.php' (e.g., 'squash-court-stats/squash-court-stats.php')
            // WordPress uses this to identify it as the SAME plugin (not a new installation)
            // If this doesn't match, WordPress will treat it as a new plugin and create duplicates
            $plugin_data = array(
                'slug' => dirname($this->plugin_slug),  // Plugin directory name (e.g., 'squash-court-stats')
                'plugin' => $this->plugin_slug,          // CRITICAL: Full plugin path (e.g., 'squash-court-stats/squash-court-stats.php')
                'new_version' => $remote_version->version,
                'url' => $remote_version->homepage,
                'package' => $remote_version->download_url,  // ZIP file download URL from GitHub release
                'icons' => array(),
                'banners' => array(),
                'tested' => $remote_version->tested,
                'requires_php' => $remote_version->requires_php,
                'compatibility' => new stdClass(),
            );
            
            // CRITICAL: Use plugin_slug as the array key
            // WordPress uses this key to match against installed plugins
            // If the key doesn't match an installed plugin, update won't appear
            $transient->response[$this->plugin_slug] = (object) $plugin_data;
        }
        
        return $transient;
    }
    
    /**
     * Get remote version information from GitHub
     * 
     * @return object|bool Remote version info or false on failure
     */
    private function get_remote_version() {
        // Check cache first
        if ($this->cache_allowed) {
            $cache = get_transient($this->cache_key);
            if ($cache !== false) {
                return $cache;
            }
        }
        
        // Fetch latest release from GitHub API
        $api_url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (empty($data) || !isset($data->tag_name)) {
            return false;
        }
        
        // Find the plugin ZIP file in release assets
        $download_url = '';
        if (!empty($data->assets)) {
            foreach ($data->assets as $asset) {
                if (strpos($asset->name, '.zip') !== false) {
                    $download_url = $asset->browser_download_url;
                    break;
                }
            }
        }
        
        // CRITICAL: If no ZIP asset found, we cannot update
        // zipball_url is a source code archive, not a WordPress plugin ZIP
        // WordPress requires a properly structured plugin ZIP file
        if (empty($download_url)) {
            error_log('Squash Court Stats: No ZIP file found in GitHub release. Release must include squash-court-stats.zip as an asset.');
            return false;  // Fail gracefully - don't offer broken update
        }
        
        // Parse version from tag (remove 'v' prefix if present)
        $version = ltrim($data->tag_name, 'v');
        
        $remote_version = (object) array(
            'version' => $version,
            'download_url' => $download_url,
            'homepage' => "https://github.com/{$this->github_repo}",
            'tested' => get_bloginfo('version'),
            'requires_php' => '7.4',
            'last_updated' => $data->published_at,
            'sections' => array(
                'description' => $this->parse_markdown($data->body),
                'changelog' => $this->parse_markdown($data->body),
            ),
        );
        
        // Cache for 12 hours
        if ($this->cache_allowed) {
            set_transient($this->cache_key, $remote_version, 12 * HOUR_IN_SECONDS);
        }
        
        return $remote_version;
    }
    
    /**
     * Provide plugin information for the update screen
     * 
     * @param object|bool $result The result object or false
     * @param string $action The type of information being requested
     * @param object $args Plugin API arguments
     * @return object|bool Modified result
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if ($args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            return $result;
        }
        
        $result = (object) array(
            'name' => 'Squash Court Stats',
            'slug' => dirname($this->plugin_slug),
            'version' => $remote_version->version,
            'author' => '<a href="https://www.itomic.com.au">Itomic Apps</a>',
            'homepage' => $remote_version->homepage,
            'requires' => '5.0',
            'tested' => $remote_version->tested,
            'requires_php' => $remote_version->requires_php,
            'download_link' => $remote_version->download_url,
            'sections' => $remote_version->sections,
            'last_updated' => $remote_version->last_updated,
        );
        
        return $result;
    }
    
    /**
     * Handle post-installation cleanup
     * CRITICAL: This ensures the plugin is installed to the correct directory
     * and WordPress recognizes it as the same plugin (not a new installation)
     * 
     * @param bool $response Installation response
     * @param array $hook_extra Extra hook data
     * @param array $result Installation result
     * @return array Modified result
     */
    public function after_install($response, $hook_extra, $result) {
        // CRITICAL: Only process our plugin's installation
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_slug) {
            return $result;
        }
        
        // Verify installation was successful
        if (is_wp_error($result)) {
            error_log('Squash Court Stats: Update installation failed: ' . $result->get_error_message());
            return $result;
        }
        
        // SIMPLIFIED: Don't interfere with WordPress's file handling
        // WordPress handles plugin installation correctly on its own
        // We only need to clear caches so the new version is recognized
        
        // Clear update cache so WordPress recognizes the new version
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
        
        // Force WordPress to rebuild plugin registry
        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache(true);
        }
        
        return $result;
    }
    
    /**
     * Display custom update message
     * 
     * @param array $plugin_data Plugin data
     * @param object $response Update response
     */
    public function update_message($plugin_data, $response) {
        if (empty($response->new_version)) {
            return;
        }
        
        echo '<br><strong>Note:</strong> This update will be downloaded from GitHub releases. ';
        echo 'Please ensure you have a backup before updating. ';
        echo 'You can enable auto-updates from the Plugins page.';
    }
    
    /**
     * Parse markdown to HTML (basic implementation)
     * 
     * @param string $markdown Markdown text
     * @return string HTML text
     */
    private function parse_markdown($markdown) {
        if (empty($markdown)) {
            return '';
        }
        
        // Basic markdown parsing
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        
        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        
        // Italic
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // Links
        $html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html);
        
        // Lists
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        
        // Line breaks
        $html = nl2br($html);
        
        return $html;
    }
    
    /**
     * Force check for updates (bypasses cache)
     */
    public function force_check() {
        $this->cache_allowed = false;
        delete_transient($this->cache_key);
        return $this->get_remote_version();
    }
}

