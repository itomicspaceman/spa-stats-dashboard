# Active Context: Squash Court Stats

## Current Work Focus

### Recently Completed
1. ✅ **Shortcode Consolidation** - Unified to single `[squash_court_stats]` shortcode
2. ✅ **In-Plugin Help System** - Added WordPress help tabs for documentation
3. ✅ **Deployment Script Improvements** - Rock-solid deployment workflow
4. ✅ **Memory Bank Setup** - Initialized Cursor Memory Bank system
5. ✅ **Documentation Updates** - Updated readme.txt and plugin description

### Current Branch
- **Branch**: `develop`
- **Status**: All changes committed and pushed
- **Latest Commit**: `036a569` - "feat: Add in-plugin help tabs and update documentation"

## Recent Changes

### Plugin Improvements
- Removed `squash_trivia` shortcode
- Added `report` parameter to `squash_court_stats`
- Unified syntax: `dashboard="name"`, `report="name"`, `charts="id"`
- Added comprehensive help tabs system
- Updated plugin description

### Deployment
- Fixed `deploy.sh` script (su vs sudo handling)
- Improved error handling and logging
- Fixed permission issues
- Verified auto-deployment workflow

### Documentation
- Created `WORDPRESS-ORG-SUBMISSION-GUIDE.md`
- Updated `readme.txt` with new shortcode syntax
- Added Memory Bank documentation

## Next Steps

### Immediate
1. Test unified shortcode syntax in WordPress
2. Verify help tabs appear correctly
3. Update any remaining documentation references

### Future Considerations
1. WordPress.org submission preparation
2. Plugin extraction script for clean WordPress.org package
3. Consider automated testing
4. Performance optimization if needed

## Active Decisions

### Shortcode Design
- **Decision**: Single base shortcode `[squash_court_stats]` with parameters
- **Rationale**: WordPress best practice, simpler for users, easier maintenance
- **Parameters**: `dashboard`, `report`, `charts`, `filter`, `title`, `class`

### Documentation Strategy
- **Primary**: In-plugin help tabs (users see immediately)
- **Secondary**: WordPress.org readme.txt (for discovery)
- **Tertiary**: External docs (GitHub, website)

### Repository Structure
- **Current**: Plugin in same repo as Laravel app
- **Future**: May need extraction script for WordPress.org submission
- **Decision**: Keep together for now (tight coupling, shared context)

## Known Issues

### None Currently
- All systems operational
- Deployment working correctly
- Documentation up to date

## Important Notes

### User Preferences
- **SSH Access**: Read-only on atlas.itomic.com unless explicit permission
- **PHP Version Checks**: Use `stats` user, not `root` (cPanel PHP Selector)
- **Database Permissions**: Granular permissions, exclude `action_events` table
- **Deployment**: Auto-deploys on push to `main` branch

### Critical Constraints
- Must not perform non-read-only actions on atlas.itomic.com without permission
- Plugin must work standalone (no Laravel dependencies)
- WordPress.org submission planned (need clean extraction)

