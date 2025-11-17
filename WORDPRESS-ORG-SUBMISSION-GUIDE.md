# WordPress.org Submission Guide

## Repository Structure for WordPress.org

### Recommended Approach: Keep in Same Repo

You can keep the plugin in the same repository as the Laravel app, but structure it for easy extraction:

```
spa/
├── app/                    # Laravel application (not in plugin)
├── resources/              # Laravel views (not in plugin)
├── routes/                 # Laravel routes (not in plugin)
├── database/               # Laravel migrations (not in plugin)
├── wordpress-plugin/       # Plugin directory (extracted for WordPress.org)
│   ├── squash-court-stats.php
│   ├── includes/
│   │   ├── class-plugin-updater.php (exclude for WordPress.org)
│   │   └── class-admin-settings.php
│   ├── assets/
│   │   └── admin/
│   ├── readme.txt
│   └── .gitignore
└── package-plugin.ps1      # Script to extract plugin
```

### WordPress.org Requirements

1. **No Laravel Dependencies**: Plugin must work standalone
2. **Clean Structure**: Only plugin files in the submission
3. **readme.txt**: Must follow WordPress.org format (you already have this)
4. **SVN Repository**: WordPress.org uses SVN, not Git
5. **No Auto-Updater**: WordPress.org handles updates automatically

### Submission Process

1. **Create SVN Repository**: WordPress.org will create one for you
2. **Extract Plugin**: Use your packaging script to create clean plugin ZIP
3. **Upload to SVN**: Initial submission via WordPress.org dashboard
4. **Future Updates**: Commit to SVN when releasing new versions

## In-Plugin Documentation

### ✅ What I've Added

The plugin now includes **Help Tabs** that appear on:
- **Plugins page** (when viewing plugin details)
- **Settings page** (if you create one)
- **Post/Page editor** (when editing content)

### Help Tabs Include:

1. **Quick Start** - Getting started guide
2. **Shortcode Reference** - Complete syntax reference
3. **Examples** - Common usage examples
4. **Help Sidebar** - Links to external resources

### How Users Access It

1. Go to **Plugins → Installed Plugins**
2. Find "Squash Court Stats"
3. Click **"Help"** button (top right of screen)
4. View help tabs with all documentation

Or when editing a page/post:
1. Add shortcode to content
2. Click **"Help"** button (top right)
3. See shortcode documentation

## Documentation Best Practices

### ✅ In-Plugin (What I Added)
- **Help Tabs** - Accessible from WordPress admin
- **readme.txt** - Shown on WordPress.org plugin page
- **Plugin Description** - Shown in plugin list

### ✅ External (What You Have)
- **GitHub README** - For developers
- **Chart Gallery** - Visual reference at stats.squashplayers.app/charts
- **Documentation Files** - SHORTCODE-USAGE-GUIDE.md, etc.

### Recommended Approach

**Primary**: In-plugin help tabs (users see it immediately)  
**Secondary**: WordPress.org readme.txt (for discovery)  
**Tertiary**: External docs (for advanced users)

## Next Steps for WordPress.org Submission

1. ✅ **Help Tabs Added** - Users can see instructions in WordPress
2. ✅ **readme.txt Updated** - Reflects unified shortcode syntax
3. ⏳ **Create Plugin Extraction Script** - To separate plugin from Laravel code
4. ⏳ **Remove Auto-Updater** - WordPress.org handles updates
5. ⏳ **Test Plugin Standalone** - Ensure no Laravel dependencies
6. ⏳ **Submit to WordPress.org** - Via their submission form

## Plugin Extraction Script

You'll need a script that:
1. Copies only plugin files (no Laravel code)
2. Excludes auto-updater (WordPress.org doesn't allow it)
3. Creates clean ZIP for submission
4. Validates structure

This can be added to your existing `package-plugin.ps1` script.

