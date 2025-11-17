# Product Context: Squash Court Stats

## Why This Project Exists

The Squash Players App maintains a global database of squash venues and courts. Previously, statistics were displayed via Zoho Analytics, which had limitations:
- External dependency on Zoho
- Limited customization
- No easy way for WordPress sites to embed statistics
- Manual categorization of venues

This project creates a self-hosted, modern solution that provides better control, customization, and integration capabilities.

## Problems It Solves

1. **Data Visualization** - Interactive maps and charts showing global squash venue distribution
2. **WordPress Integration** - Easy embedding for WordPress site owners via simple shortcodes
3. **Data Enrichment** - Automated categorization and court count discovery using AI
4. **Data Accuracy** - Automated validation of Google Place IDs and venue information
5. **User Experience** - Modern, responsive interface that works on all devices

## How It Should Work

### For End Users (WordPress Site Owners)
1. Install WordPress plugin
2. Add shortcode `[squash_court_stats]` to any page
3. Dashboard/report appears automatically
4. Plugin auto-updates from GitHub

### For Administrators
1. Data syncs automatically every 3 hours
2. AI categorizes new venues automatically
3. Court counts discovered automatically
4. Manual review queue for flagged venues
5. Comprehensive audit logs for all changes

### For Developers
1. Clear service-based architecture
2. Comprehensive documentation
3. Automated testing capabilities
4. Easy deployment workflow

## User Experience Goals

- **Simplicity** - One shortcode for all functionality
- **Performance** - Fast loading, responsive interface
- **Reliability** - Rock-solid deployment, no downtime
- **Discoverability** - In-plugin help, clear documentation
- **Flexibility** - Customizable dashboards and reports

## Target Audiences

1. **Squash Enthusiasts** - Want to see global statistics
2. **WordPress Site Owners** - Want to embed statistics on their sites
3. **Data Administrators** - Need to maintain and enrich venue data
4. **Developers** - Contributing to or maintaining the system

