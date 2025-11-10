# Active Context

## Current Phase
**Phase 0: Memory Bank & Documentation** ✅ IN PROGRESS

## Current Sprint Focus
Setting up project foundation and documentation structure before Laravel installation.

## Completed Today
- ✅ Zoho Analytics API authentication (obtained refresh token with full access)
- ✅ Remote MariaDB connection verified (atlas.itomic.com, squahliv_db)
- ✅ Database schema analysis (venues: 7,759 rows, countries: 248 rows)
- ✅ Zoho workspace identified (ID: 2371467000000013001, "Squash Players DB")
- ✅ Created comprehensive project plan (squ.plan.md)
- ✅ Memory bank structure initiated (.cursor/ directory)
- ✅ Project brief documented
- ✅ Technical architecture documented
- ✅ Coding standards documented

## Next Immediate Steps
1. ✅ Complete activeContext.md (this file)
2. ⏳ Complete progress.md
3. ⏳ Mark memory-bank todo as complete
4. ⏳ Start Phase 1: Bootstrap Laravel 12 project
5. ⏳ Configure .env with all credentials
6. ⏳ Set up remote database connection
7. ⏳ Create Eloquent models

## Blockers
**None currently**

## Decisions Needed
**None currently** - All key decisions made during planning:
- ✅ MapTiler for map tiles (free tier)
- ✅ Bootstrap 5 for styling
- ✅ File-based cache (default Laravel)
- ✅ stats.squash.players.app for production domain
- ✅ spa.test for local development

## Environment Details

### Local Development
- **OS**: Windows 11 Pro
- **Tool**: Laravel Herd
- **Domain**: spa.test
- **PHP**: 8.3 (via Herd)
- **Directory**: C:\Users\Ross Gerring\Herd\spa

### Production Target
- **Server**: cPanel hosting (squash.players.app account)
- **Domain**: stats.squash.players.app
- **PHP**: 8.3+ (via cPanel PHP Selector)
- **SSL**: Let's Encrypt (AutoSSL)

### Remote Database
- **Host**: atlas.itomic.com
- **Database**: squahliv_db
- **User**: squahliv_cursor
- **Password**: tqs]0-.KfXVW6=%.
- **Access**: SELECT only

### API Credentials

#### Zoho Analytics (Development/Validation Only)
- **Client ID**: 1000.RGW46FJ01MJCMFAQD3EMFNEPYZET5W
- **Client Secret**: b50cdab59dfb4dc434854d2a142448b29fcab67411
- **Refresh Token**: 1000.d28210a7292340a2697ce2749026978f.aa694034134032f161744b9d6f0ded28
- **API Domain**: https://www.zohoapis.com
- **Workspace ID**: 2371467000000013001
- **Scope**: ZohoAnalytics.fullaccess.all

#### MapTiler (Pending)
- **API Key**: To be obtained and stored in .env
- **Free Tier**: 100,000 map loads/month
- **Tile URL**: https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key={key}

## Current File Structure
```
spa/
├── .cursor/
│   ├── project-brief.md ✅
│   ├── technical-architecture.md ✅
│   ├── coding-standards.md ✅
│   ├── activeContext.md ✅ (this file)
│   └── progress.md ⏳ (next)
└── squ.plan.md ✅ (reference only, do not edit)
```

## Key Metrics to Track
- **Deadline**: 5 days from start (Zoho renewal)
- **Database Size**: 7,759 venues, 248 countries
- **Target Performance**: <200ms API response, <2s page load
- **Cost Savings**: $400/year (Zoho subscription)

## Reference Links
- Current Dashboard: https://squash.players.app/squash-venues-courts-country-stats/
- Zoho Analytics API: https://www.zoho.com/analytics/api/v2/
- MapLibre GL JS: https://maplibre.org/maplibre-gl-js/docs/
- Chart.js: https://www.chartjs.org/docs/latest/
- Laravel 12 Docs: https://laravel.com/docs/12.x

## Notes
- Zoho subscription expires in 5 days—hard deadline
- All Zoho API calls are for validation/comparison only
- Production system will not depend on Zoho
- Remote DB is read-only; no schema modifications allowed
- MapTiler free tier should be sufficient for expected traffic
- WordPress integration deferred to optional Phase 2

