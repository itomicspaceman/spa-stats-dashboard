# Squash Stats Dashboard WordPress Plugin

This WordPress plugin embeds the Squash Stats Dashboard from `stats.squashplayers.app` into your WordPress site using a simple shortcode.

## Features

- **Multiple Dashboards:** Choose from world stats, country stats, or venue types
- **Individual Charts:** Select specific charts to create custom dashboards
- **Visual Admin Interface:** Browse and select charts with thumbnails
- **Chart Gallery:** Preview all charts at https://stats.squashplayers.app/charts
- **Flexible Shortcodes:** Support for `dashboard` and `charts` parameters
- **Auto-Updates:** Automatic notifications for new versions (self-hosted)
- **Complete Isolation:** iframe-based embedding prevents conflicts
- **11 Available Charts:** Mix and match any combination

## Installation

### Method 1: Upload ZIP File (Recommended)

1. **Download:** Get `squash-court-stats.zip`
2. **Upload:** WordPress Admin → Plugins → Add New → Upload Plugin
3. **Install:** Click "Install Now"
4. **Activate:** Click "Activate Plugin"

### Method 2: Manual Installation

1. **Create Plugin Directory:**
   ```bash
   mkdir -p wp-content/plugins/squash-court-stats
   ```

2. **Upload Files:**
   - Copy `squash-court-stats-plugin.php` to `wp-content/plugins/squash-court-stats/`

3. **Activate Plugin:**
   - Go to WordPress Admin → Plugins
   - Find "Squash Stats Dashboard"
   - Click "Activate"

## Usage

### Admin Interface (Recommended)

1. Go to **WordPress Admin → Settings → Squash Stats**
2. Browse available dashboards and charts with thumbnails
3. Select a full dashboard or choose individual charts
4. Click **"Copy Shortcode"**
5. Paste into any page or post

### Manual Shortcode Usage

#### Full Dashboards

```
[squash_court_stats]
```
Default world dashboard with all charts

```
[squash_court_stats dashboard="country"]
```
Country-specific statistics

```
[squash_court_stats dashboard="venue-types"]
```
Venue types and categories analysis

#### Individual Charts

```
[squash_court_stats charts="venue-map"]
```
Just the interactive map

```
[squash_court_stats charts="summary-stats,top-venues,top-courts"]
```
Multiple specific charts

```
[squash_court_stats charts="venue-map,continental-breakdown,timeline"]
```
Custom combination

### Available Charts

- `summary-stats` - Key metrics overview
- `venue-map` - Interactive global map
- `continental-breakdown` - Venues & courts by continent
- `subcontinental-breakdown` - Venues & courts by sub-continent
- `timeline` - Venues added over time
- `top-venues` - Top 20 countries by venues
- `court-distribution` - Courts per venue
- `top-courts` - Top 20 countries by courts
- `venue-categories` - Venues by category
- `website-stats` - Venues with websites
- `outdoor-courts` - Top 20 countries by outdoor courts

### Examples

**Example 1: Default Dashboard**
```
[squash_court_stats]
```

**Example 2: Country Dashboard**
```
[squash_court_stats dashboard="country"]
```

**Example 3: Just the Map**
```
[squash_court_stats charts="venue-map"]
```

**Example 4: Custom Combination**
```
[squash_court_stats charts="summary-stats,venue-map,top-venues,top-courts"]
```

**Example 3: Custom CSS Class**
```
[squash_court_stats class="my-custom-class"]
```

**Example 5: With Custom CSS Class**
```
[squash_court_stats class="my-custom-class"]
```

## Technical Details

### How It Works

1. **Shortcode Registration:** Registers `[squash_court_stats]` shortcode
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
squash-court-stats/
├── squash-court-stats-plugin.php  (Main plugin file)
└── README.md                          (This file)
```

## Troubleshooting

### Shortcode Not Working

1. Make sure the plugin is activated
2. Check that you're using the exact shortcode: `[squash_court_stats]`
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
3. **Replace the Zoho iframe** with `[squash_court_stats]`
4. **Publish** - Done! The new dashboard is now live

## Support

For issues or questions:
- Email: ross@itomic.com.au
- Website: https://www.itomic.com.au

## License

GPL v2 or later

## Changelog

### 1.4.0 (2025-11-11)
- **MAJOR FEATURE:** Added multiple dashboard support (world, country, venue-types)
- **MAJOR FEATURE:** Added individual chart selection - mix and match any charts
- **NEW:** WordPress admin settings page (Settings → Squash Stats) with visual chart selector
- **NEW:** Public chart gallery at https://stats.squashplayers.app/charts
- **NEW:** 11 individual charts available for custom combinations
- **NEW:** Shortcode now supports `dashboard` and `charts` parameters
- **IMPROVED:** Modular Laravel architecture with reusable Blade components
- **IMPROVED:** Chart Registry and Dashboard Registry services for metadata
- **IMPROVED:** Dynamic chart rendering system via `/render` endpoint
- **IMPROVED:** Comprehensive documentation with shortcode examples

### 1.3.2 (2025-11-11)
- Fixed WordPress Plugin Check compatibility issues
- Made auto-updater conditional (only loads for self-hosted installations)
- Updated "Tested up to" to WordPress 6.8
- Plugin now passes WordPress.org plugin checks
- Auto-updater gracefully skips if class file not present

### 1.3.1 (2025-11-11)
- Added full-width CSS to make dashboard span entire page width
- Dashboard now breaks out of WordPress content containers
- Uses viewport width (100vw) for edge-to-edge display
- Matches width of page header and navigation

### 1.3.0 (2025-11-11) - MAJOR REFACTOR
- **Switched to iframe-based embedding** for complete isolation
- Eliminates all JavaScript and CSS conflicts with WordPress themes/plugins
- Uses postMessage API for dynamic height adjustment (no scrollbars)
- Dramatically simplified code (100 lines vs 213 lines)
- Geolocation now works properly within iframe context
- WordPress-recommended approach for embedding external content
- No more HTML fetching, asset management, or caching complexity

### 1.2.3 (2025-11-11)
- Fixed double initialization issue by removing duplicate JavaScript enqueueing
- JavaScript is now only loaded once from the fetched HTML

### 1.1.0 (2025-11-10)
- **BREAKING CHANGE:** Switched from custom URL to shortcode-based system
- Added `[squash_court_stats]` shortcode
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

