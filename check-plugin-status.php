<?php
/**
 * Plugin Status Checker
 * 
 * Upload this file to your WordPress root directory and access it via browser
 * to check the status of the Squash Court Stats plugin
 * 
 * URL: https://squash.players.app/check-plugin-status.php
 * 
 * Note: This is for squash.players.app (WordPress site)
 * 
 * DELETE THIS FILE AFTER USE FOR SECURITY
 */

// Load WordPress
require_once('wp-load.php');

// Security check - remove this check in production or add authentication
if (!current_user_can('manage_options')) {
    die('Access denied. This file should be deleted after use.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Plugin Status Checker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h1>Squash Court Stats Plugin Status Checker</h1>
    
    <?php
    $plugin_slug = 'squash-court-stats/squash-court-stats.php';
    $plugin_dir = WP_PLUGIN_DIR . '/squash-court-stats';
    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug;
    
    // Check 1: Plugin file exists
    echo '<h2>1. File System Check</h2>';
    if (file_exists($plugin_file)) {
        echo '<div class="status success">✓ Plugin file exists: ' . esc_html($plugin_file) . '</div>';
        
        // Get file info
        $file_info = stat($plugin_file);
        echo '<div class="status info">';
        echo 'File size: ' . size_format($file_info['size']) . '<br>';
        echo 'Last modified: ' . date('Y-m-d H:i:s', $file_info['mtime']) . '<br>';
        echo '</div>';
        
        // Check plugin header
        $plugin_data = get_file_data($plugin_file, array(
            'Name' => 'Plugin Name',
            'Version' => 'Version',
            'Author' => 'Author'
        ));
        
        if (!empty($plugin_data['Name'])) {
            echo '<div class="status success">';
            echo 'Plugin Name: ' . esc_html($plugin_data['Name']) . '<br>';
            echo 'Version: ' . esc_html($plugin_data['Version']) . '<br>';
            echo 'Author: ' . esc_html($plugin_data['Author']) . '<br>';
            echo '</div>';
        } else {
            echo '<div class="status error">✗ Plugin header not found or invalid</div>';
        }
    } else {
        echo '<div class="status error">✗ Plugin file MISSING: ' . esc_html($plugin_file) . '</div>';
    }
    
    // Check 2: Plugin directory
    if (is_dir($plugin_dir)) {
        echo '<div class="status success">✓ Plugin directory exists: ' . esc_html($plugin_dir) . '</div>';
        
        // List directory contents
        $files = scandir($plugin_dir);
        $files = array_diff($files, array('.', '..'));
        echo '<div class="status info">';
        echo 'Directory contents:<br>';
        echo '<pre>' . esc_html(implode("\n", $files)) . '</pre>';
        echo '</div>';
    } else {
        echo '<div class="status error">✗ Plugin directory MISSING: ' . esc_html($plugin_dir) . '</div>';
    }
    
    // Check 3: WordPress plugin registry
    echo '<h2>2. WordPress Plugin Registry</h2>';
    if (!function_exists('get_plugins')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    $all_plugins = get_plugins();
    if (isset($all_plugins[$plugin_slug])) {
        echo '<div class="status success">✓ Plugin is registered in WordPress</div>';
        echo '<div class="status info">';
        echo '<pre>' . esc_html(print_r($all_plugins[$plugin_slug], true)) . '</pre>';
        echo '</div>';
    } else {
        echo '<div class="status error">✗ Plugin NOT registered in WordPress</div>';
        echo '<div class="status warning">This means WordPress cannot see the plugin, even if files exist.</div>';
    }
    
    // Check 4: Active plugins
    echo '<h2>3. Activation Status</h2>';
    $active_plugins = get_option('active_plugins', array());
    if (in_array($plugin_slug, $active_plugins)) {
        echo '<div class="status success">✓ Plugin is ACTIVATED</div>';
    } else {
        echo '<div class="status warning">⚠ Plugin is NOT activated</div>';
    }
    
    // Check 5: Plugin can be loaded
    echo '<h2>4. Plugin Load Test</h2>';
    if (file_exists($plugin_file)) {
        // Try to include the plugin file
        ob_start();
        $error_occurred = false;
        try {
            // Check for syntax errors
            $syntax_check = shell_exec("php -l " . escapeshellarg($plugin_file) . " 2>&1");
            if (strpos($syntax_check, 'No syntax errors') !== false) {
                echo '<div class="status success">✓ No PHP syntax errors</div>';
            } else {
                echo '<div class="status error">✗ PHP syntax error detected</div>';
                echo '<div class="status error"><pre>' . esc_html($syntax_check) . '</pre></div>';
                $error_occurred = true;
            }
        } catch (Exception $e) {
            echo '<div class="status error">✗ Error checking syntax: ' . esc_html($e->getMessage()) . '</div>';
            $error_occurred = true;
        }
        ob_end_clean();
        
        if (!$error_occurred) {
            // Try to load plugin class
            if (class_exists('Squash_Stats_Dashboard')) {
                echo '<div class="status success">✓ Plugin class can be loaded</div>';
            } else {
                echo '<div class="status warning">⚠ Plugin class not found (may not be loaded yet)</div>';
            }
        }
    }
    
    // Check 6: Updater class
    echo '<h2>5. Updater Class Check</h2>';
    $updater_file = $plugin_dir . '/includes/class-plugin-updater.php';
    if (file_exists($updater_file)) {
        echo '<div class="status success">✓ Updater class file exists</div>';
    } else {
        echo '<div class="status error">✗ Updater class file MISSING</div>';
    }
    
    // Recommendations
    echo '<h2>6. Recommendations</h2>';
    if (!file_exists($plugin_file)) {
        echo '<div class="status error">';
        echo '<strong>CRITICAL:</strong> Plugin file is missing. You need to reinstall the plugin manually:<br>';
        echo '1. Download the latest ZIP from GitHub releases<br>';
        echo '2. Go to WordPress Admin → Plugins → Add New → Upload Plugin<br>';
        echo '3. Upload and activate';
        echo '</div>';
    } elseif (!isset($all_plugins[$plugin_slug])) {
        echo '<div class="status warning">';
        echo '<strong>WARNING:</strong> Plugin files exist but WordPress cannot see them.<br>';
        echo 'Possible causes:<br>';
        echo '- Plugin header is invalid<br>';
        echo '- File permissions issue<br>';
        echo '- WordPress cache issue (try clearing cache)<br>';
        echo '<br>Try: Deactivate and reactivate, or reinstall the plugin.';
        echo '</div>';
    } elseif (!in_array($plugin_slug, $active_plugins)) {
        echo '<div class="status info">';
        echo 'Plugin is installed but not activated. You can activate it from the Plugins page.';
        echo '</div>';
    } else {
        echo '<div class="status success">';
        echo '✓ Plugin appears to be working correctly!';
        echo '</div>';
    }
    ?>
    
    <hr>
    <p><strong>Security Note:</strong> DELETE THIS FILE after use!</p>
</body>
</html>

