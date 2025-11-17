# Create GitHub Release v1.2.0 - Step by Step

## Quick Link
**Click here to create the release:** https://github.com/itomic/squash-court-stats/releases/new?tag=v1.2.0&title=v1.2.0%20-%20Auto-Update%20Support

## Manual Steps

1. **Go to the releases page:**
   https://github.com/itomic/squash-court-stats/releases/new

2. **Fill in the form:**
   - **Tag:** `v1.2.0` (should already be selected since we pushed the tag)
   - **Release title:** `v1.2.0 - Auto-Update Support`
   
3. **Copy this into the description box:**

```markdown
## What's New in v1.2.0

### ✨ New Features
- **Automatic Update Checking**: Plugin now checks GitHub for new releases every 12 hours
- **WordPress Auto-Update Support**: Users can enable/disable auto-updates from the Plugins page
- **Update Notifications**: Get notified when new versions are available

### 🐛 Bug Fixes
- Fixed API URLs to use absolute paths for cross-domain embedding
- Improved caching for better performance

### 📦 Installation
1. Download `squash-stats-dashboard.zip`
2. Upload to WordPress (Plugins → Add New → Upload Plugin)
3. Activate and use `[squash_stats_dashboard]` shortcode

### 🔄 Upgrading from v1.1.0
- Simply install v1.2.0 over the existing installation
- All settings and shortcodes will continue to work
- Future updates will be automatic (if enabled)

---

## How Auto-Updates Work

1. **Automatic Checking**: Every 12 hours, the plugin checks GitHub for new releases
2. **Update Notification**: When a new version is available, WordPress shows an "Update available" message
3. **One-Click Update**: Click "Update now" to install the latest version
4. **Auto-Update Option**: Enable "Enable auto-updates" to have WordPress automatically install updates

---

**Full Changelog**: https://github.com/itomic/squash-court-stats/compare/v1.1.0...v1.2.0
```

4. **Upload the plugin ZIP:**
   - Drag and drop `squash-stats-dashboard.zip` into the "Attach binaries" section at the bottom
   - Or click "Attach binaries by dropping them here or selecting them" and browse to the file

5. **Publish:**
   - Make sure "Set as the latest release" is checked
   - Click "Publish release"

## File Location
The ZIP file is located at:
`C:\Users\Ross Gerring\Herd\spa\squash-stats-dashboard.zip`

## After Publishing
Once published, the plugin's auto-update system will automatically detect this release within 12 hours for any WordPress site that has the plugin installed!

