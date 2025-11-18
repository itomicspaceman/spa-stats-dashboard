# Update Reliability Guide

## Why This Matters

**Critical:** When users install your plugin, they should **NEVER** need to manually delete and reinstall to get updates. A broken update system means:
- ❌ Poor user experience
- ❌ Support burden ("please delete and reinstall")
- ❌ Risk of data loss if users delete before backing up
- ❌ Loss of trust in your plugin

## What Makes Updates Reliable

### 1. Correct Plugin Slug (CRITICAL)

The plugin slug must be **exactly** `squash-court-stats/squash-court-stats.php`:
- WordPress uses this to identify the plugin
- If it doesn't match, WordPress treats it as a NEW plugin (not an update)
- This causes duplicate installations and breaks updates

**Our Implementation:**
```php
$this->plugin_slug = plugin_basename(__FILE__);
// Returns: 'squash-court-stats/squash-court-stats.php'
```

### 2. Correct ZIP Structure (CRITICAL)

The ZIP file **MUST** have this structure:
```
squash-court-stats.zip
└── squash-court-stats/
    ├── squash-court-stats.php
    ├── readme.txt
    ├── README.md
    └── includes/
        └── class-plugin-updater.php
```

**Why:** WordPress extracts the ZIP and expects the plugin folder at the root. If the structure is wrong:
- WordPress installs to wrong location
- Plugin isn't recognized as the same plugin
- Update fails or creates duplicate

**Our Packaging:**
- ✅ Both `package-plugin.ps1` and `package-plugin.sh` create correct structure
- ✅ GitHub Actions workflow uses the same structure
- ✅ ZIP contains `squash-court-stats/` folder at root

### 3. Version Comparison (CRITICAL)

WordPress must be able to compare versions:
- Current version: Read from plugin file header
- Remote version: Extracted from GitHub release tag
- Comparison: `version_compare()` must work correctly

**Our Implementation:**
```php
// Current version from plugin file
$this->version = $plugin_data['Version'];  // e.g., "1.5.0"

// Remote version from GitHub tag
$version = ltrim($data->tag_name, 'v');  // "v1.6.0" → "1.6.0"

// Compare
version_compare($this->version, $remote_version->version, '<')
```

### 4. Download URL (CRITICAL)

The download URL must:
- Point to a ZIP file (not source code)
- Be publicly accessible (no authentication)
- Be from GitHub release assets (not zipball)

**Our Implementation:**
- ✅ Checks release assets for `.zip` file
- ✅ Uses `browser_download_url` (public access)
- ✅ Fails gracefully if no ZIP found (prevents broken updates)

### 5. Post-Installation Handling (CRITICAL)

After WordPress downloads and extracts the ZIP, we must:
- Ensure plugin is in correct directory
- Clear update cache
- Preserve user settings/data

**Our Implementation:**
```php
// Only process our plugin (not others)
if ($hook_extra['plugin'] !== $this->plugin_slug) {
    return $result;
}

// Move to correct location if needed
if ($result['destination'] !== $plugin_dir) {
    $wp_filesystem->move($result['destination'], $plugin_dir, true);
}

// Clear cache
delete_transient($this->cache_key);
```

## Common Failure Points

### ❌ Wrong ZIP Structure

**Problem:** ZIP contains files at root, not in plugin folder
```
squash-court-stats.zip
├── squash-court-stats.php  ❌ Wrong!
└── includes/
```

**Result:** WordPress installs to wrong location, plugin not recognized

**Prevention:** ✅ Our packaging scripts create correct structure

### ❌ Wrong Plugin Slug

**Problem:** Slug mismatch between installed and update
- Installed: `squash-court-stats/squash-court-stats.php`
- Update thinks: `squash-court-stats-plugin/squash-court-stats.php`

**Result:** WordPress treats as new plugin, creates duplicate

**Prevention:** ✅ We use `plugin_basename(__FILE__)` consistently

### ❌ Missing ZIP in Release

**Problem:** GitHub release has no ZIP file attached

**Result:** Update fails or tries to use zipball (source code, not plugin)

**Prevention:** ✅ We check for ZIP and fail gracefully if missing

### ❌ Version Format Mismatch

**Problem:** Version formats don't match
- Plugin file: `Version: 1.5.0`
- Release tag: `1.5.0` (no 'v' prefix)
- Or: `v1.5.0` (with 'v' prefix)

**Result:** Version comparison fails, update not detected

**Prevention:** ✅ We strip 'v' prefix: `ltrim($data->tag_name, 'v')`

### ❌ Directory Mismatch After Install

**Problem:** WordPress installs to `squash-court-stats-1/` instead of `squash-court-stats/`

**Result:** Plugin not recognized, appears as new installation

**Prevention:** ✅ `after_install()` hook moves to correct location

## Testing Checklist

Before releasing an update, verify:

- [ ] ZIP structure is correct (plugin folder at root)
- [ ] ZIP contains all required files
- [ ] Version number matches release tag
- [ ] Release has ZIP file attached (not just source)
- [ ] Plugin slug is consistent (`squash-court-stats/squash-court-stats.php`)
- [ ] Update appears in WordPress (Plugins page)
- [ ] Update installs successfully (one-click)
- [ ] Plugin still works after update
- [ ] Settings/data preserved
- [ ] No duplicate installations created

## Error Handling

Our implementation includes:

1. **Graceful Failures:**
   - If no ZIP found → return false (don't offer broken update)
   - If API fails → return false (don't break WordPress)
   - If version check fails → skip update (don't force)

2. **User-Friendly Messages:**
   - Clear error messages if update fails
   - Success notifications after checking
   - Helpful troubleshooting info

3. **Logging:**
   - Errors logged to WordPress error log
   - Helps diagnose issues without breaking site

## Best Practices We Follow

✅ **Consistent Plugin Slug** - Always use `plugin_basename(__FILE__)`  
✅ **Correct ZIP Structure** - Plugin folder at root of ZIP  
✅ **Version Matching** - Tag matches plugin file version  
✅ **ZIP in Release** - Always attach ZIP file to releases  
✅ **Post-Install Cleanup** - Ensure correct directory  
✅ **Error Handling** - Fail gracefully, don't break WordPress  
✅ **Cache Management** - Clear caches after updates  
✅ **Security** - Nonce verification, permission checks  

## Summary

**Our update system is designed to be bulletproof:**

1. ✅ Correct plugin identification (slug matching)
2. ✅ Correct ZIP structure (WordPress-compatible)
3. ✅ Reliable version comparison
4. ✅ Proper download URLs (ZIP files, not source)
5. ✅ Post-installation directory correction
6. ✅ Graceful error handling
7. ✅ User-friendly notifications

**Result:** Users can update with one click, no manual deletion/reinstallation needed.

