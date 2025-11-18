# Plugin Recovery Guide

## If Plugin Disappears After Update

If the plugin disappears from the WordPress plugins list after an update attempt, follow these steps:

### Step 1: Check if Plugin Files Exist

Via cPanel File Manager or SSH, check if the plugin directory exists:
```
/wp-content/plugins/squash-court-stats/
```

And verify the main plugin file exists:
```
/wp-content/plugins/squash-court-stats/squash-court-stats.php
```

### Step 2: Check for PHP Errors

1. Check WordPress debug log: `/wp-content/debug.log`
2. Check server error logs (cPanel → Error Logs)
3. Look for any "Squash Court Stats" error messages

### Step 3: Check Plugin Status in Database

The plugin might be deactivated. Check the `wp_options` table:
- Look for `active_plugins` option
- See if `squash-court-stats/squash-court-stats.php` is listed

### Step 4: Quick Recovery Options

#### Option A: Manual Reinstall (Safest)

1. Download the latest plugin ZIP from GitHub releases
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload and activate the plugin

#### Option B: Reactivate via Database (Advanced)

If files exist but plugin is missing from list:

1. Access phpMyAdmin or database tool
2. Find `wp_options` table (prefix may vary)
3. Find `active_plugins` row
4. Edit the serialized array to include:
   ```
   a:1:{i:0;s:45:"squash-court-stats/squash-court-stats.php";}
   ```
   (Adjust serialized format based on your existing plugins)

#### Option C: Fix via WP-CLI (If Available)

```bash
wp plugin activate squash-court-stats
```

### Step 5: Verify Plugin Structure

The plugin directory should contain:
```
squash-court-stats/
├── squash-court-stats.php (main file)
├── includes/
│   └── class-plugin-updater.php
└── readme.txt
```

### Prevention

The updated `after_install` method now includes:
- Better error checking
- Verification that plugin file exists before moving
- More detailed error logging

## Common Causes

1. **File Move Failed**: The `after_install` method tried to move files but failed
2. **Fatal PHP Error**: Plugin has a syntax error preventing it from loading
3. **Permission Issues**: WordPress couldn't write to plugin directory
4. **Directory Structure**: ZIP extraction created wrong directory structure

## Getting Help

If the plugin is missing:
1. Check error logs first
2. Verify files exist
3. Try manual reinstall
4. Check GitHub Issues for known problems

