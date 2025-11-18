<?php
/**
 * Temporary script to force WordPress to check for plugin updates
 * 
 * Usage:
 * 1. Place this file in your WordPress root directory
 * 2. Visit: https://wordpress.test/force-check-updates.php
 * 3. Delete this file after testing
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('update_plugins')) {
    die('You must be an administrator to run this script.');
}

// Clear the update cache for our plugin
$cache_key = 'squash_dashboard_update_' . md5('itomic/squash-court-stats');
delete_transient($cache_key);

// Clear all update transients
delete_site_transient('update_plugins');

// Force WordPress to check for updates
wp_update_plugins();

echo '<h1>Update Check Forced</h1>';
echo '<p>Cache cleared and update check triggered.</p>';
echo '<p><a href="' . admin_url('plugins.php') . '">Go to Plugins Page</a></p>';
echo '<p><a href="' . admin_url('update-core.php') . '">Go to Updates Page</a></p>';
echo '<p style="color: red;"><strong>Remember to delete this file after testing!</strong></p>';

