# Release v1.2.0 - Auto-Update Support

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

## Manual Release Creation Steps

1. Go to https://github.com/itomic/squash-court-stats/releases/new
2. Tag version: `v1.2.0`
3. Release title: `v1.2.0 - Auto-Update Support`
4. Copy the description above into the release notes
5. Upload `squash-stats-dashboard.zip` as a release asset
6. Click "Publish release"

---

**Full Changelog**: https://github.com/itomic/squash-court-stats/commits/main

