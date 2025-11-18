<?php
/**
 * Debug script to test shortcode rendering
 * Place this in your WordPress root and access via browser
 * DELETE AFTER USE
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo '<h1>Shortcode Debug Test</h1>';

// Test if class exists
if (class_exists('Squash_Stats_Dashboard')) {
    echo '<p style="color: green;">✓ Squash_Stats_Dashboard class exists</p>';
    
    // Test if shortcode is registered
    global $shortcode_tags;
    if (isset($shortcode_tags['squash_court_stats'])) {
        echo '<p style="color: green;">✓ Shortcode is registered</p>';
        
        // Try to render the shortcode
        echo '<h2>Testing Shortcode Rendering</h2>';
        echo '<div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">';
        
        try {
            $output = do_shortcode('[squash_court_stats dashboard="world"]');
            if (!empty($output)) {
                echo '<p style="color: green;">✓ Shortcode rendered successfully</p>';
                echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0;">';
                echo htmlspecialchars(substr($output, 0, 500)) . '...';
                echo '</div>';
            } else {
                echo '<p style="color: orange;">⚠ Shortcode returned empty output</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;">✗ Error: ' . esc_html($e->getMessage()) . '</p>';
        } catch (Error $e) {
            echo '<p style="color: red;">✗ Fatal Error: ' . esc_html($e->getMessage()) . '</p>';
            echo '<p>File: ' . esc_html($e->getFile()) . '</p>';
            echo '<p>Line: ' . esc_html($e->getLine()) . '</p>';
        }
        
        echo '</div>';
    } else {
        echo '<p style="color: red;">✗ Shortcode is NOT registered</p>';
    }
} else {
    echo '<p style="color: red;">✗ Squash_Stats_Dashboard class does NOT exist</p>';
}

// Check plugin file
$plugin_file = WP_PLUGIN_DIR . '/squash-court-stats/squash-court-stats.php';
echo '<h2>Plugin File Check</h2>';
if (file_exists($plugin_file)) {
    echo '<p style="color: green;">✓ Plugin file exists: ' . esc_html($plugin_file) . '</p>';
    
    // Check if it's loaded
    $active_plugins = get_option('active_plugins', array());
    $plugin_slug = 'squash-court-stats/squash-court-stats.php';
    if (in_array($plugin_slug, $active_plugins)) {
        echo '<p style="color: green;">✓ Plugin is activated</p>';
    } else {
        echo '<p style="color: red;">✗ Plugin is NOT activated</p>';
    }
} else {
    echo '<p style="color: red;">✗ Plugin file MISSING: ' . esc_html($plugin_file) . '</p>';
}

// Check includes directory
$includes_dir = WP_PLUGIN_DIR . '/squash-court-stats/includes';
echo '<h2>Includes Directory Check</h2>';
if (is_dir($includes_dir)) {
    echo '<p style="color: green;">✓ Includes directory exists</p>';
    
    $updater_file = $includes_dir . '/class-plugin-updater.php';
    if (file_exists($updater_file)) {
        echo '<p style="color: green;">✓ Updater class file exists</p>';
    } else {
        echo '<p style="color: orange;">⚠ Updater class file missing (optional)</p>';
    }
} else {
    echo '<p style="color: orange;">⚠ Includes directory missing (optional)</p>';
}

// Check PHP version
echo '<h2>PHP Version</h2>';
echo '<p>PHP Version: ' . PHP_VERSION . '</p>';
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo '<p style="color: green;">✓ PHP version is compatible</p>';
} else {
    echo '<p style="color: red;">✗ PHP version too old (requires 7.4+)</p>';
}

// Check WordPress functions
echo '<h2>WordPress Functions Check</h2>';
$required_functions = ['esc_attr', 'esc_url', 'esc_js', 'shortcode_atts', 'http_build_query'];
foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo '<p style="color: green;">✓ ' . $func . '() exists</p>';
    } else {
        echo '<p style="color: red;">✗ ' . $func . '() MISSING</p>';
    }
}

echo '<hr><p><strong>Security Note:</strong> DELETE THIS FILE after debugging!</p>';

