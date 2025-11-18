# Testing Auto-Update on Live Site

## Current Situation

- **Live site:** Version 1.5.0 installed
- **Latest version:** 1.6.0 (with all new features)
- **Goal:** Test the update mechanism from 1.5.0 → 1.6.0

## Test Plan

### Step 1: Create GitHub Release for 1.6.0

1. **Package the plugin** (already done):
   ```powershell
   .\package-plugin.ps1
   ```
   This creates `squash-court-stats.zip`

2. **Create GitHub Release:**
   - Go to: https://github.com/itomic/squash-court-stats/releases/new
   - **Tag:** `v1.6.0` (must match version in plugin file)
   - **Title:** `v1.6.0 - Auto-update improvements and responsive design`
   - **Description:**
     ```markdown
     ## Version 1.6.0
     
     ### New Features
     - ✅ "Check for updates" button in plugin row and settings page
     - ✅ Improved responsive design for all WordPress themes
     - ✅ Full-width display option (`fullwidth="true"`)
     - ✅ Enhanced auto-update reliability
     - ✅ WordPress auto-update opt-in support
     
     ### Improvements
     - Better compatibility with Gutenberg, Elementor, and other page builders
     - Improved error handling for update failures
     - Enhanced post-installation directory handling
     
     ### Technical
     - GitHub Actions workflow for automated releases
     - Improved ZIP structure validation
     - Better cache management
     ```
   - **Attach file:** Upload `squash-court-stats.zip`
   - Click **"Publish release"**

### Step 2: Test Update on Live Site

1. **Go to live site Plugins page:**
   - https://squash.players.app/wp-admin/plugins.php

2. **Check for updates:**
   - Click "Check for updates" link below "Squash Court Stats"
   - OR go to Settings → Squash Court Stats → "Check for updates now"

3. **Verify update appears:**
   - Should see "Update available" badge
   - Should show version 1.6.0 available

4. **Perform the update:**
   - Click "Update now" button
   - WordPress downloads from GitHub
   - WordPress installs automatically
   - Verify version updates to 1.6.0

5. **Verify plugin still works:**
   - Check any pages using the shortcode
   - Verify settings page loads
   - Confirm no errors

## What This Tests

✅ **Plugin slug matching** - WordPress recognizes it as same plugin  
✅ **ZIP structure** - Correct format for WordPress  
✅ **Version comparison** - 1.5.0 → 1.6.0 detected correctly  
✅ **Download & install** - ZIP downloads and extracts properly  
✅ **Directory handling** - Installs to correct location  
✅ **No duplicates** - Doesn't create new installation  
✅ **Settings preserved** - User settings remain intact  

## If Update Fails

**Don't panic!** The update system is designed to fail gracefully:

1. **Check error message** - WordPress will show what went wrong
2. **Check error logs** - Look for specific error messages
3. **Verify release** - Ensure ZIP file is attached to GitHub release
4. **Check version** - Ensure release tag matches plugin version
5. **Clear cache** - Try "Check for updates" again

**Fallback:** If update fails, you can manually upload the ZIP file:
- Go to Plugins → Add New → Upload Plugin
- Upload `squash-court-stats.zip`
- WordPress will recognize it as an update (not new install)

## Success Criteria

✅ Update appears in Plugins page  
✅ "Update now" button works  
✅ Update installs successfully  
✅ Version shows as 1.6.0  
✅ Plugin still works after update  
✅ No duplicate installations  
✅ Settings preserved  

## After Testing

Once confirmed working:
- ✅ Update mechanism is proven reliable
- ✅ Future updates will work automatically
- ✅ Users can update with one click
- ✅ No manual deletion/reinstallation needed

