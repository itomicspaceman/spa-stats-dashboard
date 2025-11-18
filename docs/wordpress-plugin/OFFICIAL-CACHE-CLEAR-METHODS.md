# Official WordPress Methods to Clear Plugin Cache

## Method 1: WP-CLI (Most Official - Recommended)

Since you have SSH access to `atlas.itomic.com`, use WP-CLI commands:

```bash
# Navigate to WordPress root
cd /path/to/wordpress

# Clear plugin cache (rebuilds plugin registry)
wp plugin list --allow-root

# Clear all update caches
wp cache flush --allow-root

# Or use WordPress core function via WP-CLI shell
wp eval 'wp_clean_plugins_cache(true);' --allow-root
wp eval 'wp_clean_update_cache();' --allow-root
```

**Note:** Replace `/path/to/wordpress` with the actual WordPress root directory for `squash.players.app`.

## Method 2: WordPress Core Functions

WordPress provides official functions:

### `wp_clean_plugins_cache()`
- Clears the plugins cache used by `get_plugins()`
- Rebuilds the plugin registry
- Clears plugin updates cache (if `true` is passed)

### `wp_clean_update_cache()`
- Clears update caches for plugins, themes, and core

### Usage via WP-CLI Shell:
```bash
wp eval 'wp_clean_plugins_cache(true); wp_clean_update_cache();' --allow-root
```

### Usage via PHP (one-time script):
```php
<?php
require_once('wp-load.php');
wp_clean_plugins_cache(true);
wp_clean_update_cache();
echo "Cache cleared!";
```

## Method 3: Official WordPress Plugins

If you prefer a plugin-based solution:

1. **Clear Cache Everywhere** - https://wordpress.org/plugins/clear-cache-everywhere/
2. **WP-CLI** - Already available via SSH

## Recommended Approach for Your Situation

Since you have SSH access, use **Method 1 (WP-CLI)**:

```bash
# SSH into atlas.itomic.com
ssh root@atlas.itomic.com

# Find WordPress root (likely in /home/squashpl/public_html or similar)
cd /home/squashpl/public_html  # Adjust path as needed

# Clear plugin cache and rebuild registry
wp plugin list --allow-root

# Clear all caches
wp cache flush --allow-root

# Force plugin registry rebuild
wp eval 'wp_clean_plugins_cache(true);' --allow-root
```

## Verification

After clearing cache, check if plugin appears:
```bash
wp plugin list --allow-root | grep squash
```

Or visit: `https://squash.players.app/wp-admin/plugins.php`

