<?php
/**
 * Fix Wordfence Plugin Registry Issue
 * 
 * This script checks and fixes the corrupted Wordfence plugin registration
 * in the WordPress database.
 * 
 * DELETE AFTER USE
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Wordfence Registry</title>
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
    <h1>Fix Wordfence Plugin Registry</h1>
    
    <?php
    if (isset($_POST['action']) && $_POST['action'] === 'fix') {
        // Get current active plugins
        $active_plugins = get_option('active_plugins', array());
        
        echo '<h2>Before Fix</h2>';
        echo '<div class="status info">Active plugins count: ' . count($active_plugins) . '</div>';
        echo '<pre>' . esc_html(print_r($active_plugins, true)) . '</pre>';
        
        // Find and remove corrupted Wordfence entry
        $corrupted_entry = 'squash-court-stats/wordfence.php';
        $fixed = false;
        
        if (in_array($corrupted_entry, $active_plugins)) {
            $active_plugins = array_values(array_diff($active_plugins, array($corrupted_entry)));
            update_option('active_plugins', $active_plugins);
            $fixed = true;
            echo '<div class="status success">✓ Removed corrupted Wordfence entry: ' . esc_html($corrupted_entry) . '</div>';
        } else {
            echo '<div class="status info">No corrupted entry found in active_plugins</div>';
        }
        
        // Clear plugin cache
        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache(true);
            echo '<div class="status success">✓ Plugin cache cleared</div>';
        }
        
        // Clear update cache
        if (function_exists('wp_clean_update_cache')) {
            wp_clean_update_cache();
            echo '<div class="status success">✓ Update cache cleared</div>';
        }
        
        echo '<h2>After Fix</h2>';
        $new_active_plugins = get_option('active_plugins', array());
        echo '<div class="status info">Active plugins count: ' . count($new_active_plugins) . '</div>';
        echo '<pre>' . esc_html(print_r($new_active_plugins, true)) . '</pre>';
        
        if ($fixed) {
            echo '<div class="status success">';
            echo '<strong>✓ Fix completed!</strong><br>';
            echo 'The corrupted Wordfence entry has been removed from the database.<br>';
            echo 'WordPress will rebuild the plugin registry on the next page load.';
            echo '</div>';
        }
        
        echo '<p><a href="' . admin_url('plugins.php') . '" class="button safe">Go to Plugins Page</a></p>';
        
    } else {
        // Show current status
        echo '<h2>Current Status</h2>';
        
        $active_plugins = get_option('active_plugins', array());
        $corrupted_entry = 'squash-court-stats/wordfence.php';
        
        echo '<div class="status info">Total active plugins: ' . count($active_plugins) . '</div>';
        
        if (in_array($corrupted_entry, $active_plugins)) {
            echo '<div class="status error">✗ CORRUPTED ENTRY FOUND: ' . esc_html($corrupted_entry) . '</div>';
            echo '<div class="status warning">This entry points to a non-existent plugin file and needs to be removed.</div>';
        } else {
            echo '<div class="status success">✓ No corrupted entry found in active_plugins</div>';
        }
        
        // Check if Wordfence exists in proper location
        $wordfence_paths = array(
            'wordfence/wordfence.php',
            'wordfence-security/wordfence.php'
        );
        
        $wordfence_found = false;
        foreach ($wordfence_paths as $path) {
            $file = WP_PLUGIN_DIR . '/' . $path;
            if (file_exists($file)) {
                echo '<div class="status success">✓ Wordfence found at: ' . esc_html($path) . '</div>';
                $wordfence_found = true;
                break;
            }
        }
        
        if (!$wordfence_found) {
            echo '<div class="status warning">⚠ Wordfence not found in expected locations</div>';
        }
        
        // Show all active plugins
        echo '<h3>All Active Plugins</h3>';
        echo '<pre>' . esc_html(print_r($active_plugins, true)) . '</pre>';
        
        // Fix button
        if (in_array($corrupted_entry, $active_plugins)) {
            echo '<h2>Fix Action</h2>';
            echo '<form method="post">';
            echo '<button type="submit" name="action" value="fix" class="safe">Remove Corrupted Entry & Clear Cache</button>';
            echo '</form>';
            echo '<div class="status warning">';
            echo '<strong>What this will do:</strong><br>';
            echo '1. Remove "squash-court-stats/wordfence.php" from active_plugins<br>';
            echo '2. Clear plugin cache<br>';
            echo '3. Clear update cache<br>';
            echo '4. WordPress will rebuild plugin registry';
            echo '</div>';
        }
    }
    ?>
    
    <hr>
    <p><strong>Security Note:</strong> DELETE THIS FILE after use!</p>
</body>
</html>

