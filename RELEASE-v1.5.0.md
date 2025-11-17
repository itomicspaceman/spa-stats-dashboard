# Create GitHub Release v1.5.0 - Step by Step

## Quick Link
**Click here to create the release:** https://github.com/itomic/squash-court-stats/releases/new?tag=v1.5.0&title=v1.5.0%20-%20Trivia%20Page%20%26%20Auto-Updates

## Manual Steps

1. **Go to the releases page:**
   https://github.com/itomic/squash-court-stats/releases/new

2. **Fill in the form:**
   - **Tag:** `v1.5.0` (create new tag)
   - **Release title:** `v1.5.0 - Trivia Page & Auto-Updates`
   
3. **Copy this into the description box:**

```markdown
## What's New in v1.5.0

### ✨ Major Features

#### 🎲 Native Trivia Page Integration
- **New `[squash_trivia]` shortcode** - Display fun facts and statistics about squash worldwide
- **10 Interactive Sections:**
  1. Countries without squash venues
  2. High altitude venues (2000m+)
  3. Most northerly and southerly venues
  4. Hotels & resorts with squash courts
  5. Population & area statistics
  6. Venues with unknown court counts
  7. The 100% Country Club
  8. Countries word cloud visualization
  9. Loneliest squash courts
  10. Squash court graveyard (closed/deleted venues)

#### 🗺️ Interactive Visualizations
- **Leaflet Maps** - Interactive maps for all geographic sections
- **Sortable Tables** - Click column headers to sort data
- **WordCloud2.js** - Visual representation of countries by venue count
- **Filters & Tabs** - Filter by continent, country, and more
- **Responsive Design** - Perfect on desktop and mobile devices

#### 🔄 Auto-Update System
- **Automatic Update Checking** - Plugin checks GitHub for new releases every 12 hours
- **WordPress Integration** - Native WordPress update notifications
- **One-Click Updates** - Update directly from WordPress admin
- **Auto-Update Support** - Enable automatic updates from Plugins page

### 🎨 Design & UX
- Modern purple gradient theme
- Smooth transitions and hover effects
- Color-coded badges for elevation, distance, and status
- Comprehensive CSS styling (500+ lines)
- Mobile-first responsive design

### 🔧 Technical Improvements
- **Facebook API Integration** - Enhanced venue data collection
- **Court Count Analyzer** - Improved website verification and distinction between estate/club websites
- **Search Optimization** - Excludes squash.players.app from searches to avoid circular references
- **Plugin Updater Class** - Full GitHub releases integration
- **Asset Management** - Proper CSS and JavaScript loading

### 📚 Documentation
- **TRIVIA-SHORTCODE-GUIDE.md** - Complete usage guide for trivia shortcode
- **TRIVIA-IMPLEMENTATION-SUMMARY.md** - Technical documentation
- Updated **SHORTCODE-USAGE-GUIDE.md** with trivia examples
- Comprehensive inline code documentation

### 📦 Installation & Usage

**Install:**
1. Download `squash-stats-dashboard.zip`
2. Upload to WordPress (Plugins → Add New → Upload Plugin)
3. Activate the plugin

**Use:**
```
[squash_court_stats]  - Full stats dashboard
[squash_trivia]           - Full trivia page
[squash_trivia section="high-altitude"]  - Specific section
```

### 🔄 Upgrading from v1.4.0

- Simply install v1.5.0 over the existing installation
- All existing shortcodes will continue to work
- New `[squash_trivia]` shortcode is immediately available
- Future updates will be automatic (if enabled)

### 🐛 Bug Fixes
- Fixed plugin updater configuration
- Improved packaging script to include assets folder
- Enhanced court count search logic
- Better error handling for API calls

---

## Available Trivia Sections

Use the `section` parameter to display specific trivia sections:

- `countries-without-venues` - Countries without squash venues
- `high-altitude` - High altitude venues (2000m+)
- `extreme-latitude` - Most northerly and southerly venues
- `hotels-resorts` - Hotels and resorts with squash courts
- `population-area` - Venues and courts by population and land area
- `unknown-courts` - Venues with unknown number of courts
- `country-club` - The 100% Country Club
- `word-cloud` - Countries by number of venues (word cloud)
- `loneliest` - Loneliest squash courts
- `graveyard` - Squash court graveyard (closed/deleted venues)

---

## How Auto-Updates Work

1. **Automatic Checking**: Every 12 hours, the plugin checks GitHub for new releases
2. **Update Notification**: When a new version is available, WordPress shows an "Update available" message
3. **One-Click Update**: Click "Update now" to install the latest version
4. **Auto-Update Option**: Enable "Enable auto-updates" to have WordPress automatically install updates

---

## Benefits of Native Trivia Integration

✅ **Better Performance** - Direct API calls, no iframe overhead
✅ **SEO Benefits** - Content is indexed by search engines
✅ **Native Integration** - Matches your WordPress theme
✅ **Responsive** - Perfect on mobile devices
✅ **Customizable** - Easy to style with custom CSS
✅ **Interactive** - Sortable tables, filters, clickable maps

---

**Full Changelog**: https://github.com/itomic/squash-court-stats/compare/v1.4.0...v1.5.0
```

4. **Upload the plugin ZIP:**
   - First, package the plugin: Run `.\package-plugin.ps1` in PowerShell
   - Drag and drop `squash-stats-dashboard.zip` into the "Attach binaries" section at the bottom
   - Or click "Attach binaries by dropping them here or selecting them" and browse to the file

5. **Publish:**
   - Make sure "Set as the latest release" is checked
   - Click "Publish release"

## File Location
The ZIP file will be located at:
`C:\Users\Ross Gerring\Herd\spa\squash-stats-dashboard.zip`

## Before Publishing - Package the Plugin

Run this command in PowerShell:
```powershell
cd "C:\Users\Ross Gerring\Herd\spa"
.\package-plugin.ps1
```

This will create the `squash-stats-dashboard.zip` file with all necessary files:
- Main plugin file
- Auto-updater class
- Trivia CSS and JavaScript
- Documentation

## After Publishing

Once published:
1. The plugin's auto-update system will detect this release within 12 hours
2. WordPress sites with the plugin installed will see an update notification
3. Users can click "Update now" for one-click updates
4. Or enable auto-updates for automatic installation

## Testing the Auto-Update

After creating the release, you can test it:
1. Install v1.4.0 on a test WordPress site
2. Wait 12 hours (or clear the transient cache)
3. Go to Plugins page
4. You should see "Update available" for Squash Stats Dashboard
5. Click "Update now" to test the update process

## Notes

- The auto-updater checks GitHub every 12 hours
- Updates are cached to avoid excessive API calls
- The plugin will show update notifications in WordPress admin
- Users can enable/disable auto-updates from the Plugins page

