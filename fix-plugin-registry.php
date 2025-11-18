<?php
/**
 * Plugin Registry Fixer
 * 
 * This script fixes WordPress plugin registry issues
 * Upload to WordPress root and access via browser
 * DELETE AFTER USE
 */

/**
 * Plugin Registry Fixer
 * 
 * For: squash.players.app (WordPress site)
 * Upload to WordPress root and access via browser
 * DELETE AFTER USE
 */

require_once('wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Plugin Registry Fixer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .danger { background: #dc3545; color: white; }
        .safe { background: #28a745; color: white; }
    </style>
</head>
<body>
    <h1>Plugin Registry Fixer</h1>
    
    <?php
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'clear_cache') {
            // Clear plugin cache
            delete_site_transient('update_plugins');
            delete_transient('squash_dashboard_update_' . md5('itomic/squash-court-stats'));
            
            // Force WordPress to rebuild plugin list
            if (!function_exists('get_plugins')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            // Clear opcode cache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            echo '<div class="status success">✓ Plugin cache cleared. WordPress will rebuild plugin list on next page load.</div>';
            echo '<p><a href="' . admin_url('plugins.php') . '">Go to Plugins page</a></p>';
        }
        
        if ($action === 'rebuild_registry') {
            // Force WordPress to rebuild plugin registry
            if (!function_exists('get_plugins')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            // This forces a rebuild
            $all_plugins = get_plugins();
            
            echo '<div class="status success">✓ Plugin registry rebuilt. Found ' . count($all_plugins) . ' plugins.</div>';
            
            // Show Squash Court Stats if found
            $plugin_slug = 'squash-court-stats/squash-court-stats.php';
            if (isset($all_plugins[$plugin_slug])) {
                echo '<div class="status success">✓ Squash Court Stats found in registry!</div>';
                echo '<div class="status info"><pre>' . esc_html(print_r($all_plugins[$plugin_slug], true)) . '</pre></div>';
            } else {
                echo '<div class="status warning">⚠ Squash Court Stats NOT found in registry</div>';
            }
            
            echo '<p><a href="' . admin_url('plugins.php') . '">Go to Plugins page</a></p>';
        }
    } else {
        // Show current status
        echo '<h2>Current Status</h2>';
        
        if (!function_exists('get_plugins')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $all_plugins = get_plugins();
        $plugin_slug = 'squash-court-stats/squash-court-stats.php';
        
        echo '<div class="status info">Total plugins in registry: ' . count($all_plugins) . '</div>';
        
        if (isset($all_plugins[$plugin_slug])) {
            echo '<div class="status success">✓ Squash Court Stats is in registry</div>';
            echo '<div class="status info">';
            echo 'Name: ' . esc_html($all_plugins[$plugin_slug]['Name']) . '<br>';
            echo 'Version: ' . esc_html($all_plugins[$plugin_slug]['Version']) . '<br>';
            echo 'Plugin URI: ' . esc_html($all_plugins[$plugin_slug]['PluginURI']) . '<br>';
            echo '</div>';
        } else {
            echo '<div class="status error">✗ Squash Court Stats NOT in registry</div>';
        }
        
        // Check if files exist
        $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug;
        if (file_exists($plugin_file)) {
            echo '<div class="status success">✓ Plugin file exists: ' . esc_html($plugin_file) . '</div>';
        } else {
            echo '<div class="status error">✗ Plugin file MISSING: ' . esc_html($plugin_file) . '</div>';
        }
        
        // Check active plugins
        $active_plugins = get_option('active_plugins', array());
        if (in_array($plugin_slug, $active_plugins)) {
            echo '<div class="status success">✓ Plugin is activated</div>';
        } else {
            echo '<div class="status warning">⚠ Plugin is NOT activated</div>';
        }
        
        // Actions
        echo '<h2>Fix Actions</h2>';
        echo '<form method="post">';
        echo '<button type="submit" name="action" value="clear_cache" class="safe">Clear Plugin Cache</button>';
        echo '<button type="submit" name="action" value="rebuild_registry" class="safe">Rebuild Plugin Registry</button>';
        echo '</form>';
        
        echo '<div class="status warning">';
        echo '<strong>If plugin is still missing after these actions:</strong><br>';
        echo '1. Go to Plugins page<br>';
        echo '2. If plugin appears but is deactivated, activate it<br>';
        echo '3. If plugin still doesn\'t appear, you may need to manually reinstall';
        echo '</div>';
    }
    ?>
    
    <hr>
    <p><strong>Security Note:</strong> DELETE THIS FILE after use!</p>
</body>
</html>

