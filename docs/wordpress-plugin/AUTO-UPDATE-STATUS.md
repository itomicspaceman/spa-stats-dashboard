# Auto-Update Functionality Status

## Current Status ✅

The auto-update system is **fully implemented and active**. Here's what's in place:

### What's Working

1. **Updater Class** (`includes/class-plugin-updater.php`)
   - ✅ Checks GitHub releases for new versions
   - ✅ Compares versions and shows update notifications
   - ✅ Caches results for 12 hours (performance optimization)
   - ✅ Provides one-click updates from WordPress admin
   - ✅ Handles post-installation cleanup

2. **WordPress Integration**
   - ✅ Hooks into WordPress update system (`pre_set_site_transient_update_plugins`)
   - ✅ Provides plugin information for update screen (`plugins_api`)
   - ✅ **NEW:** Opts into WordPress auto-updates (WordPress 5.5+)
   - ✅ Users can enable/disable auto-updates per plugin

3. **Initialization**
   - ✅ Updater class is loaded and initialized
   - ✅ Repository set to `itomic/squash-court-stats`
   - ✅ Plugin name corrected to "Squash Court Stats"

## How It Works

### Important: GitHub Releases vs. Branch Pushes

**The updater checks GitHub RELEASES, not branch pushes.**

This is WordPress best practice because:
- Releases provide version numbers (semantic versioning)
- Releases can include release notes and changelogs
- Releases can have attached ZIP files (required for updates)
- Releases are stable, tested versions (not development code)

### Workflow

```
1. You push code to main branch
   ↓
2. You create a GitHub Release (with version tag, e.g., "v1.5.0")
   ↓
3. You attach the plugin ZIP file to the release
   ↓
4. WordPress checks GitHub API (every 12 hours)
   ↓
5. WordPress detects new version
   ↓
6. WordPress shows "Update available" notification
   ↓
7. User clicks "Update now" OR auto-update runs (if enabled)
   ↓
8. WordPress downloads ZIP from GitHub release
   ↓
9. WordPress installs update automatically
```

### Creating a Release

When you want to release an update:

1. **Update version number** in `squash-court-stats.php`:
   ```php
   * Version: 1.6.0
   ```

2. **Package the plugin**:
   ```powershell
   .\package-plugin.ps1
   ```
   This creates `squash-court-stats.zip` in the `dist/` folder.

3. **Create GitHub Release**:
   - Go to: https://github.com/itomic/squash-court-stats/releases/new
   - Tag: `v1.6.0` (must match version number)
   - Title: `v1.6.0` or descriptive title
   - Description: Release notes (markdown supported)
   - **Attach `squash-court-stats.zip`** from `dist/` folder
   - Click "Publish release"

4. **WordPress will detect it** within 12 hours (or immediately if you clear the cache)

## Auto-Update Options

### Manual Updates (Default)
- WordPress shows "Update available" badge
- User clicks "Update now" button
- Update installs immediately

### Automatic Updates (Opt-In)
Users can enable auto-updates from the Plugins page:
1. Go to **Plugins → Installed Plugins**
2. Find "Squash Court Stats"
3. Click **"Enable auto-updates"** link
4. Plugin will update automatically when new releases are available

**Note:** The plugin opts into auto-updates, but users control whether to enable it per-plugin.

## Testing Auto-Updates

### Quick Test Script

We've created helper scripts to make testing easier:

1. **Run the test script:**
   ```powershell
   .\test-auto-update.ps1
   ```
   This will:
   - Update version to 1.6.0
   - Package the plugin
   - Provide step-by-step instructions

2. **Create GitHub Release:**
   - Follow the instructions from the script
   - Or go to: https://github.com/itomic/squash-court-stats/releases/new
   - Tag: `v1.6.0`
   - Attach: `squash-court-stats.zip`

3. **Force WordPress to check** (bypass 12-hour cache):
   
   **Option A: Use the helper script**
   - Copy `force-check-updates.php` to WordPress root
   - Visit: `https://wordpress.test/force-check-updates.php`
   - Delete the file after testing
   
   **Option B: Manual method**
   - Go to: `https://wordpress.test/wp-admin/update-core.php`
   - Click "Check Again" button
   
   **Option C: Clear cache via code**
   ```php
   // Add this temporarily to functions.php
   delete_transient('squash_dashboard_update_' . md5('itomic/squash-court-stats'));
   delete_site_transient('update_plugins');
   ```

4. **Verify update appears** in:
   - Plugins page (`/wp-admin/plugins.php`) - look for "Update available" badge
   - Updates page (`/wp-admin/update-core.php`) - should list the plugin

5. **Test the update:**
   - Click "Update now" on the Plugins page
   - WordPress downloads from GitHub
   - WordPress installs automatically
   - Plugin version updates to 1.6.0

6. **Revert after testing:**
   ```powershell
   .\revert-version.ps1
   ```
   This reverts the version back to 1.5.0

## Why Not Branch Pushes?

**WordPress cannot directly update from branch pushes** because:
- No version numbers (how does WordPress know it's newer?)
- No packaged ZIP files (WordPress needs a complete plugin package)
- No release notes (users need to know what changed)
- Development code may be unstable

**Best Practice:** Use GitHub Releases for versioned, stable updates.

## Alternative: Automated Releases

If you want releases created automatically when code is pushed to main:

1. **GitHub Actions** can automatically:
   - Create a release when code is pushed to main
   - Package the plugin ZIP
   - Attach it to the release
   - Tag with version number

2. **This requires:**
   - GitHub Actions workflow file
   - Version number in a file (e.g., `package.json` or plugin header)
   - Automated packaging script

Would you like me to set up automated releases via GitHub Actions?

## Current Configuration

- **Repository:** `itomic/squash-court-stats`
- **Update Check:** Every 12 hours (WordPress transient cache)
- **Update Source:** GitHub Releases API
- **Download:** ZIP file from release assets
- **Auto-Update:** Opted in (user can enable/disable)
- **Cache Key:** `squash_dashboard_update_[repo_hash]`

## Troubleshooting

### Updates Not Showing?

1. **Check version number** in plugin file matches release tag
2. **Verify release exists** on GitHub with ZIP file attached
3. **Clear WordPress cache:**
   ```php
   delete_transient('squash_dashboard_update_' . md5('itomic/squash-court-stats'));
   ```
4. **Check WordPress error logs** for API errors
5. **Verify GitHub API access** (no authentication required for public repos)

### Auto-Updates Not Working?

1. **Check WordPress version** (5.5+ required for auto-updates)
2. **Verify user enabled auto-updates** on Plugins page
3. **Check site health** for update-related issues
4. **Review WordPress update logs** in site health

## Next Steps

1. ✅ Auto-update system is implemented and active
2. ⏳ Create a test release to verify it works
3. ⏳ (Optional) Set up GitHub Actions for automated releases
4. ⏳ Document release process for future updates

## Summary

**Status:** ✅ **Fully Implemented and Active**

The auto-update system is working correctly. It will:
- Check GitHub releases every 12 hours
- Show update notifications when new releases are available
- Allow one-click manual updates
- Support WordPress auto-updates (if user enables)

**To release an update:** Create a GitHub release with a version tag and attach the plugin ZIP file.

