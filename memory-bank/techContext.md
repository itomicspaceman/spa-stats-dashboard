# Technical Context: Squash Court Stats

## Technology Stack

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.3+ (8.3.26 on production)
- **Database**: MariaDB (remote connection to atlas.itomic.com)
- **Cache**: Laravel Cache (file/redis)
- **Queue**: Laravel Queue (if needed)

### Frontend
- **CSS Framework**: Bootstrap 5.3.2
- **Charts**: Chart.js 4.4.0 with DataLabels plugin
- **Maps**: MapLibre GL JS 4.0.0
- **Icons**: Font Awesome 6.5.1
- **Build Tool**: Vite
- **JavaScript**: ES6+ modules

### External APIs
- **Google Places (New) API** - Venue information
- **Google Places Text Search API** - Finding expired Place IDs
- **Google Translate API** - Multilingual support
- **OpenAI API** - Categorization and court count analysis
- **Facebook Graph API** - Public page information (optional)

### WordPress Integration
- **Plugin**: Standalone PHP plugin
- **Integration Method**: iframe-based
- **Auto-Updates**: GitHub releases via custom updater
- **Help System**: WordPress help tabs

## Development Setup

### Local Environment
- **Laravel Herd**: Local development server
- **Project Path**: `C:\Users\Ross Gerring\Herd\spa`
- **URL**: `https://spa.test`
- **PHP Version**: 8.3.x (via Herd)

### Database Connection
- **Host**: Remote MariaDB on atlas.itomic.com
- **Database**: `squahliv_db`
- **User**: `squahliv_cursor` (read/write permissions)
- **Connection**: Configured in `.env` with `SQUASH_DB_*` variables

### Production Environment
- **Server**: atlas.itomic.com (cPanel)
- **User**: `stats`
- **Path**: `/home/stats/current`
- **Public**: `/home/stats/public_html`
- **PHP**: 8.3.x (via cPanel PHP Selector)
- **Node.js**: 24 (via cPanel)
- **URL**: `https://stats.squashplayers.app`

## Dependencies

### PHP (Composer)
- Laravel 12
- Standard Laravel packages
- No custom packages required

### Node.js (npm)
- Vite
- Chart.js and plugins
- Bootstrap (via CDN in production)

### WordPress Plugin
- No external dependencies
- Self-contained PHP file
- Optional: GitHub updater class

## Technical Constraints

### Database
- **Read-Only Access**: Some tables (action_events excluded per user request)
- **Remote Connection**: Network latency considerations
- **Indexes**: Managed on remote database
- **Permissions**: Granular permissions for `squahliv_cursor` user

### API Limits
- **Google Places**: Rate limits apply
- **OpenAI**: Cost considerations (using gpt-4o-mini for court counts)
- **Translation**: Caching to minimize API calls

### Deployment
- **cPanel Git Version Control**: Repository management
- **GitHub Webhooks**: Automated deployment triggers
- **File Permissions**: Must be 755 for web server access
- **PHP Version**: Must match between local and production

## Development Workflow

### Branch Strategy
- **main**: Production-ready code (auto-deploys)
- **develop**: Development branch (merge to main for releases)

### Git Workflow
1. Work on `develop` branch
2. Test locally
3. Commit and push to `develop`
4. Merge to `main` when ready
5. Auto-deployment triggers on push to `main`

### Testing
- Local testing on `spa.test`
- Manual testing before deployment
- No automated test suite (yet)

## Build Process

### Frontend Assets
```bash
npm run build  # Production build
npm run dev    # Development with HMR
```

### Laravel Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Deployment Script
`deploy.sh` handles:
1. Git pull
2. File sync
3. npm install & build
4. Cache clearing
5. Permission fixing
6. Symlink management

