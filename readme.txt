=== Squash Court Stats ===
Contributors: itomicapps
Tags: squash, statistics, dashboard, sports, analytics
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.5.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embeds the Squash Court Stats Dashboard from stats.squashplayers.app into your WordPress site using a simple shortcode.

== Description ==

The Squash Court Stats plugin allows you to embed comprehensive squash venue and court statistics directly into your WordPress pages and posts using a simple shortcode.

**Features:**

* **Multiple Dashboards:** Choose from world stats, country stats, or venue types dashboards
* **Trivia Page:** Fun facts, maps, and statistics about squash worldwide
* **Individual Charts:** Select specific charts to create custom dashboards
* **Visual Admin Interface:** Browse and select charts with thumbnails in WordPress admin
* **Chart Gallery:** Public gallery to preview all available charts before installing
* **Shortcode Based:** Use `[squash_court_stats]` with `dashboard`, `report`, or `charts` parameters
* **Auto-Updates:** Automatic update notifications from GitHub releases
* **Complete Isolation:** iframe-based embedding prevents CSS/JS conflicts
* **Multiple Instances:** Use on multiple pages with different chart combinations

**Data Displayed:**

* Interactive global map of squash venues
* Continental and sub-continental breakdowns
* Top countries by venues and courts
* Venue categories and statistics
* Timeline of venue additions
* Court type distributions
* And much more!

**Source Code:**

This plugin pulls data from a Laravel application hosted at stats.squashplayers.app. The full source code for both the plugin and the Laravel dashboard is available on GitHub:

* Plugin & Dashboard Source: [https://github.com/itomic/squash-court-stats](https://github.com/itomic/squash-court-stats)

The dashboard is built using Laravel 12 and Vite for asset compilation. See the GitHub repository for build instructions.

== Installation ==

**Automatic Installation:**

1. Go to WordPress Admin → Plugins → Add New → Upload Plugin
2. Choose `squash-stats-dashboard.zip`
3. Click "Install Now"
4. Click "Activate Plugin"

**Manual Installation:**

1. Upload the `squash-stats-dashboard` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress

**Usage:**

1. Go to WordPress Admin → Settings → Squash Stats
2. Browse available dashboards and charts
3. Select a full dashboard or choose individual charts
4. Click "Copy Shortcode"
5. Paste the shortcode into any page or post
6. Publish!

**Or manually:**

1. Create a new WordPress page (or edit an existing one)
2. Add a shortcode (see examples below)
3. Publish!

== Frequently Asked Questions ==

= How do I use this plugin? =

Go to Settings → Squash Stats in WordPress admin to browse and select charts. Or manually add a shortcode to any page:

**Full Dashboards:**
* `[squash_court_stats]` - Default world dashboard
* `[squash_court_stats dashboard="country"]` - Country statistics
* `[squash_court_stats dashboard="venue-types"]` - Venue types analysis

**Individual Charts:**
* `[squash_court_stats charts="venue-map"]` - Just the map
* `[squash_court_stats charts="summary-stats,top-venues"]` - Multiple charts
* `[squash_court_stats charts="venue-map,continental-breakdown,timeline"]` - Custom combination

**Reports (Trivia Sections):**
* `[squash_court_stats report="graveyard"]` - Squash court graveyard
* `[squash_court_stats report="high-altitude"]` - High altitude venues
* `[squash_court_stats report="loneliest"]` - Loneliest courts
* `[squash_court_stats report="word-cloud"]` - Countries word cloud

= What dashboards are available? =

* **World Stats** - Complete global overview with all charts
* **Country Stats** - Country-specific analysis
* **Venue Types** - Focus on venue categories and characteristics

= What individual charts can I use? =

11 charts available: summary-stats, venue-map, continental-breakdown, subcontinental-breakdown, timeline, top-venues, court-distribution, top-courts, venue-categories, website-stats, outdoor-courts

View the full gallery at https://stats.squashplayers.app/charts

= Can I customize the appearance? =

Yes! You can add custom CSS classes:
* `[squash_court_stats class="my-custom-class"]`

= Can I use this on multiple pages? =

Yes! You can use the shortcode on as many pages as you like.

= Where does the data come from? =

The data is pulled from stats.squashplayers.app, which aggregates squash venue and court information from the Squash Players App database.

= Is the source code available? =

Yes! The full source code is available on GitHub at https://github.com/itomic/squash-court-stats

= Does this work with page builders? =

Yes! The shortcode should work with most page builders that support WordPress shortcodes (Elementor, Beaver Builder, Divi, etc.).

== Screenshots ==

1. Global squash venue map with interactive clusters
2. Continental breakdown of venues and courts
3. Top countries by squash venues
4. Dashboard statistics overview

== Changelog ==

= 1.5.0 (2025-11-17) =
* **MAJOR FEATURE:** Added Squash Trivia page with iframe-based embedding
* **NEW:** `[squash_trivia]` shortcode for embedding trivia sections
* **NEW:** 9 interactive trivia sections (countries without venues, high altitude, extreme latitude, hotels & resorts, population & area, unknown courts, country club, word cloud, loneliest venues, graveyard)
* **NEW:** Section parameter for displaying specific trivia sections
* **NEW:** Filter parameter for geographic filtering
* **NEW:** Auto-update system for seamless plugin updates from GitHub
* **IMPROVED:** Court count search now uses OpenAI's native web search
* **IMPROVED:** Better website distinction (estate vs club websites)
* **IMPROVED:** Facebook page integration for venue information
* **IMPROVED:** Search excludes squash.players.app to avoid circular references
* **IMPROVED:** Complete isolation via iframe prevents CSS/JS conflicts
* **IMPROVED:** Uses postMessage API for dynamic height adjustment
* **FIXED:** Plugin updater now properly configured for auto-updates
* **FIXED:** API URL corrected from /api to /squash prefix

= 1.4.0 (2025-11-11) =
* **MAJOR FEATURE:** Added multiple dashboard support (world, country, venue-types)
* **MAJOR FEATURE:** Added individual chart selection - mix and match any charts
* **NEW:** WordPress admin settings page with visual chart selector
* **NEW:** Public chart gallery at stats.squashplayers.app/charts
* **NEW:** 11 individual charts available for custom combinations
* **NEW:** Shortcode now supports `dashboard` and `charts` parameters
* **IMPROVED:** Modular architecture with reusable Blade components
* **IMPROVED:** Chart Registry and Dashboard Registry services
* **IMPROVED:** Dynamic chart rendering system
* **IMPROVED:** Comprehensive documentation with examples

= 1.3.2 (2025-11-11) =
* Fixed WordPress Plugin Check compatibility issues
* Made auto-updater conditional (only loads for self-hosted installations)
* Updated "Tested up to" to WordPress 6.8
* Plugin now passes WordPress.org plugin checks

= 1.3.1 (2025-11-11) =
* Added full-width CSS to make dashboard span entire page width
* Dashboard now breaks out of WordPress content containers
* Uses viewport width (100vw) for edge-to-edge display
* Matches width of page header and navigation

= 1.3.0 (2025-11-11) =
* **MAJOR REFACTOR:** Switched to iframe-based embedding for complete isolation
* Eliminates all JavaScript and CSS conflicts with WordPress themes/plugins
* Uses postMessage API for dynamic height adjustment (no scrollbars)
* Dramatically simplified code (100 lines vs 213 lines)
* Geolocation now works properly within iframe context
* WordPress-recommended approach for embedding external content

= 1.2.3 (2025-11-11) =
* Fixed double initialization issue by removing duplicate JavaScript enqueueing
* JavaScript is now only loaded once from the fetched HTML

= 1.2.0 (2025-11-11) =
* Added automatic update checking from GitHub releases
* Plugin now notifies when new versions are available
* Supports WordPress auto-update system
* Fixed API URLs to use absolute paths for cross-domain embedding
* Improved caching for better performance

= 1.1.0 (2025-11-10) =
* Added shortcode-based system for maximum flexibility
* Added optional `height` and `class` parameters
* Removed template-based approach for simpler implementation
* Smart asset enqueueing (only loads on pages with shortcode)
* Complete flexibility - use on any page/URL
* Fixed plugin packaging for clean WordPress installation and deletion

= 1.0.0 (2025-11-10) =
* Initial release
* Direct HTML injection (no iframe)
* Intelligent caching system
* WordPress integration

== Upgrade Notice ==

= 1.1.0 =
Major update! Switched to shortcode-based system. If upgrading from 1.0.0, you'll need to use the shortcode instead of the custom URL.

== External Services ==

This plugin connects to the following external services:

**stats.squashplayers.app**
* Purpose: Fetches dashboard HTML content and statistics data
* Privacy: No personal data is transmitted. The service only provides public statistics.
* Terms: https://squash.players.app/

**CDN Services (for frontend libraries):**
* unpkg.com - MapLibre GL JS library
* cdn.jsdelivr.net - Chart.js and plugins
* cdnjs.cloudflare.com - Font Awesome icons
* fonts.openmaptiles.org - Map font glyphs

All external services are loaded only on pages that use the `[squash_court_stats]` shortcode.

