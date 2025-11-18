<?php
require_once('wp-load.php');

if (!function_exists('get_plugins')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$all_plugins = get_plugins();
$active_plugins = get_option('active_plugins', array());

echo "=== All Plugins ===\n";
foreach ($all_plugins as $slug => $data) {
    if (stripos($slug, 'squash') !== false || stripos($data['Name'], 'Squash') !== false) {
        echo "FOUND: $slug\n";
        echo "  Name: " . $data['Name'] . "\n";
        echo "  Version: " . $data['Version'] . "\n";
        echo "  Active: " . (in_array($slug, $active_plugins) ? 'YES' : 'NO') . "\n";
        echo "\n";
    }
}

echo "=== Checking for squash-court-stats.php ===\n";
$plugin_file = WP_PLUGIN_DIR . '/squash-court-stats/squash-court-stats.php';
if (file_exists($plugin_file)) {
    echo "EXISTS: $plugin_file\n";
} else {
    echo "MISSING: $plugin_file\n";
}

echo "\n=== Directory Contents ===\n";
$plugin_dir = WP_PLUGIN_DIR . '/squash-court-stats';
if (is_dir($plugin_dir)) {
    $files = scandir($plugin_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  $file\n";
        }
    }
}

