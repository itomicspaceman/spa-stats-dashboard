# Plugin Rename Summary: "Squash Stats Dashboard" → "Squash Court Stats"

## ✅ What Has Been Completed

All code changes have been made and committed to the `develop` branch:

### Files Renamed
- ✅ `squash-stats-dashboard-plugin.php` → `squash-court-stats.php`

### Code Updates
- ✅ Plugin headers (Plugin Name, Description, Update URI)
- ✅ GitHub repo references: `itomic/spa-stats-dashboard` → `itomic/squash-court-stats`
- ✅ All package scripts (`.ps1`, `.sh` files)
- ✅ WordPress `readme.txt`
- ✅ All documentation files (`.md` files)
- ✅ Laravel views (dashboard-layout.blade.php, charts-gallery.blade.php)
- ✅ Plugin updater class references

### What Stayed the Same (For Backward Compatibility)
- ✅ Shortcode names: `[squash_court_stats]` and `[squash_trivia]` (unchanged)
- ✅ Class names: `Squash_Stats_Dashboard` (unchanged)
- ✅ Function names (unchanged)

## 🔧 Manual Steps Required

### 1. Rename GitHub Repository ⚠️ **REQUIRED**

**Current:** `itomic/spa-stats-dashboard`  
**New:** `itomic/squash-court-stats`

**Steps:**
1. Go to: https://github.com/itomic/spa-stats-dashboard/settings
2. Scroll down to "Danger Zone"
3. Click "Change repository name"
4. Enter: `squash-court-stats`
5. Click "I understand, change repository name"

**Note:** GitHub will automatically redirect old URLs, but you should update all references.

### 2. Update GitHub Webhook (If Configured) ⚠️ **REQUIRED**

If you have a webhook configured for auto-deployment:

1. Go to: https://github.com/itomic/squash-court-stats/settings/hooks
2. Edit the existing webhook
3. The webhook URL should still work, but verify it's correct
4. Test the webhook after renaming

### 3. Update Server Deployment Scripts (If Needed)

If your server deployment scripts reference the old repository name:

**Check these files on the server:**
- `/home/stats/repo/.git/config` (remote URL)
- `/home/stats/deploy.sh` (if it has hardcoded repo name)
- `/home/stats/repo/webhook-deploy.php` (if it references repo name)

**Update remote URL:**
```bash
ssh root@atlas.itomic.com
cd /home/stats/repo
git remote set-url origin https://github.com/itomic/squash-court-stats.git
```

### 4. Update Local Git Remote (If Needed)

After renaming the GitHub repo, update your local remote:

```bash
cd "c:\Users\Ross Gerring\Herd\spa"
git remote set-url origin https://github.com/itomic/squash-court-stats.git
```

### 5. Test the Plugin

After renaming:
1. Package the plugin: `.\package-plugin.ps1`
2. Install on a test WordPress site
3. Verify shortcodes still work
4. Verify auto-updates work (if configured)

## 📦 New Plugin Package

After renaming, the plugin will package as:
- **File:** `squash-court-stats.zip`
- **Directory:** `squash-court-stats/`
- **Main file:** `squash-court-stats.php`

## 🔄 Migration Path for Existing Installations

**For users with the old plugin installed:**
- The plugin will continue to work (same shortcodes)
- When they update, WordPress will recognize it as the same plugin (same class names)
- The plugin name in WordPress admin will change to "Squash Court Stats"

**No breaking changes for end users!**

## ✅ Verification Checklist

After completing manual steps:

- [ ] GitHub repository renamed
- [ ] Local git remote updated
- [ ] Server git remote updated (if applicable)
- [ ] Webhook still works (if configured)
- [ ] Plugin packages correctly (`.\package-plugin.ps1`)
- [ ] Plugin installs on WordPress
- [ ] Shortcodes work: `[squash_court_stats]` and `[squash_trivia]`
- [ ] Auto-updates work (if configured)

## 📝 Notes

- **Shortcodes unchanged:** This ensures backward compatibility
- **Class names unchanged:** Prevents breaking existing installations
- **GitHub redirects:** Old URLs will redirect automatically for 90 days
- **Documentation:** All docs updated to reflect new name

## 🚀 Next Steps

1. **Rename GitHub repository** (Step 1 above)
2. **Update git remotes** (Steps 3-4 above)
3. **Test everything** (Step 5 above)
4. **Merge to main** when ready: `git checkout main && git merge develop && git push origin main`

---

**Commit:** `6d96911` - All code changes complete and ready for GitHub repo rename.

