# Plugin Check Warnings Explained

## What You're Seeing

The WordPress Plugin Check tool is showing warnings about:
- "Plugin Updater detected"
- "Update URI header is not allowed in plugins hosted on WordPress.org"
- "Detected code which may be altering WordPress update routines"

## Why These Warnings Appear

**These warnings are EXPECTED and NOT a problem** for self-hosted plugins.

The Plugin Check tool is designed for plugins that will be submitted to WordPress.org. WordPress.org has strict rules:
- ❌ No custom update checkers
- ❌ No Update URI header (they manage updates)
- ❌ No modification of WordPress update routines

**However**, we're NOT submitting to WordPress.org (yet). We're using GitHub for distribution, which REQUIRES:
- ✅ Custom update checker (to check GitHub releases)
- ✅ Update URI header (points to GitHub)
- ✅ Modification of update routines (to integrate with GitHub)

## This is Correct Behavior

The `Update URI: https://github.com/itomic/squash-court-stats` header tells WordPress:
- "This plugin is self-hosted, not on WordPress.org"
- "Use the custom updater to check GitHub for updates"
- "Don't check WordPress.org for updates"

## When You Submit to WordPress.org

When you're ready to submit to WordPress.org:

1. **Remove the Update URI header** (WordPress.org manages updates)
2. **Remove the custom updater class** (`includes/class-plugin-updater.php`)
3. **Remove updater initialization code**
4. **Use WordPress.org's built-in update system**

The Plugin Check warnings will disappear once these are removed.

## For Now: These Warnings Are Safe to Ignore

✅ **Your plugin works correctly**  
✅ **Updates work correctly**  
✅ **These warnings are informational only**  
✅ **They don't affect functionality**

The warnings are just telling you: "This won't work on WordPress.org" - which is fine, because you're not on WordPress.org yet!

## Summary

- **Current status:** Self-hosted via GitHub ✅
- **Warnings:** Expected and harmless ✅
- **Functionality:** Works correctly ✅
- **Future:** When submitting to WordPress.org, remove custom updater ✅

