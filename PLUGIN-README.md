# Squash Stats Dashboard WordPress Plugin

This WordPress plugin embeds the Squash Stats Dashboard from `stats.squashplayers.app` into your WordPress site using a simple shortcode.

## Installation

### Method 1: Upload ZIP File (Recommended)

1. **Download:** Get `squash-stats-dashboard.zip`
2. **Upload:** WordPress Admin → Plugins → Add New → Upload Plugin
3. **Install:** Click "Install Now"
4. **Activate:** Click "Activate Plugin"

### Method 2: Manual Installation

1. **Create Plugin Directory:**
   ```bash
   mkdir -p wp-content/plugins/squash-stats-dashboard
   ```

2. **Upload Files:**
   - Copy `squash-stats-dashboard-plugin.php` to `wp-content/plugins/squash-stats-dashboard/`

3. **Activate Plugin:**
   - Go to WordPress Admin → Plugins
   - Find "Squash Stats Dashboard"
   - Click "Activate"

## Usage

### Basic Shortcode

Simply add this shortcode to any WordPress page or post:

```
[squash_stats_dashboard]
```

### Examples

**Example 1: Simple Usage**
1. Create a new page: "Squash Stats - NEW"
2. Set the URL slug to: `squash-venues-courts-world-stats-new`
3. Add the shortcode: `[squash_stats_dashboard]`
4. Publish!

**Example 2: Custom Height**
```
[squash_stats_dashboard height="2000px"]
```

**Example 3: Custom CSS Class**
```
[squash_stats_dashboard class="my-custom-class"]
```

**Example 4: Both**
```
[squash_stats_dashboard height="2000px" class="full-width-dashboard"]
```

## Features

- ✅ **Shortcode Based:** Use `[squash_stats_dashboard]` anywhere
- ✅ **Flexible:** Create any page/URL you want
- ✅ **No iFrame:** Direct HTML injection for better performance and SEO
- ✅ **Asset Optimization:** Loads CSS/JS from stats.squashplayers.app
- ✅ **Smart Caching:** Intelligent caching of manifest and content
- ✅ **WordPress Integration:** Works seamlessly with your WordPress theme
- ✅ **Multiple Instances:** Use on multiple pages if needed
- ✅ **Customizable:** Optional height and CSS class parameters

## Technical Details

### How It Works

1. **Shortcode Registration:** Registers `[squash_stats_dashboard]` shortcode
2. **Content Fetching:** Pulls HTML content from `https://stats.squashplayers.app`
3. **Asset Loading:** Dynamically loads Vite-built assets using manifest.json
4. **Smart Enqueueing:** Only loads assets on pages that use the shortcode
5. **Caching Strategy:**
   - Manifest cached for 1 hour
   - Content cached for 5 minutes
   - Automatic cache invalidation

### Dependencies

The plugin loads these external assets:
- MapLibre GL JS (4.0.0)
- Chart.js (4.4.0)
- Chart.js Datalabels Plugin (2.2.0)
- Font Awesome (6.5.1)
- Dashboard CSS/JS from stats.squashplayers.app

### File Structure

```
squash-stats-dashboard/
├── squash-stats-dashboard-plugin.php  (Main plugin file)
└── README.md                          (This file)
```

## Troubleshooting

### Shortcode Not Working

1. Make sure the plugin is activated
2. Check that you're using the exact shortcode: `[squash_stats_dashboard]`
3. Try viewing the page in an incognito window (cache issue)

### Assets Not Loading

1. Check that `https://stats.squashplayers.app` is accessible
2. Clear WordPress transient cache:
   - Delete transient: `squash_dashboard_manifest`
   - Delete transient: `squash_dashboard_content`

### Content Not Updating

The content is cached for 5 minutes. To force refresh:
1. Delete the `squash_dashboard_content` transient
2. Or wait 5 minutes for automatic refresh

## Migration Path

When ready to replace the old Zoho Analytics page:

1. **Test thoroughly** on your new page (e.g., `/squash-venues-courts-world-stats-new/`)
2. **Edit the old page** at `/squash-venues-courts-world-stats/`
3. **Replace the Zoho iframe** with `[squash_stats_dashboard]`
4. **Publish** - Done! The new dashboard is now live

## Support

For issues or questions:
- Email: ross@itomic.com.au
- Website: https://www.itomic.com.au

## License

GPL v2 or later

## Changelog

### 1.1.0 (2025-11-10)
- **BREAKING CHANGE:** Switched from custom URL to shortcode-based system
- Added `[squash_stats_dashboard]` shortcode
- Added optional `height` and `class` parameters
- Removed template-based approach for simpler implementation
- Smart asset enqueueing (only loads on pages with shortcode)
- Complete flexibility - use on any page/URL

### 1.0.0 (2025-11-10)
- Initial release
- Custom page at `/squash-venues-courts-world-stats-new/`
- Direct HTML injection (no iframe)
- Intelligent caching system
- WordPress integration

