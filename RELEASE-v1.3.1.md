# Squash Stats Dashboard v1.3.1

## Full-Width Dashboard Display

This release adds CSS to make the dashboard span the full width of the WordPress page, matching the width of the site header and navigation.

### What's New

✨ **Full-Width Display**
- Dashboard now spans entire page width (100vw)
- Breaks out of WordPress content containers
- Matches width of page header and navigation
- Uses standard CSS technique for edge-to-edge display

🎨 **Smart CSS Loading**
- CSS only loads when shortcode is present on a page
- No performance impact on pages without the dashboard
- Conditional loading via `wp_head` hook

### Technical Details

The plugin now wraps the iframe in a `.squash-dashboard-wrapper` div with CSS that uses the viewport width technique:

```css
.squash-dashboard-wrapper {
    width: 100vw;
    position: relative;
    left: 50%;
    right: 50%;
    margin-left: -50vw;
    margin-right: -50vw;
    max-width: 100vw;
}
```

This is a well-known CSS pattern for breaking out of content containers while maintaining responsive behavior.

### Installation

**For existing users:**
1. Go to WordPress Admin → Plugins
2. Click "Check for updates" or wait for automatic check (every 12 hours)
3. Click "Update now" when v1.3.1 appears
4. Done! The dashboard will now display full-width

**For new users:**
1. Download `squash-stats-dashboard.zip` from this release
2. Upload to WordPress (Plugins → Add New → Upload Plugin)
3. Activate the plugin
4. Add `[squash_court_stats]` to any page

### Upgrade Notes

- ✅ Safe to upgrade - no breaking changes
- ✅ Existing shortcodes continue to work
- ✅ No configuration changes needed
- ✅ Automatic height adjustment still works

### Compatibility

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Tested up to**: WordPress 6.7

### Full Changelog

See [PLUGIN-README.md](PLUGIN-README.md) for complete version history.

---

**Previous releases:**
- v1.3.0: Major refactor to iframe + postMessage
- v1.2.3: Fixed double initialization
- v1.2.0: Added auto-update system

