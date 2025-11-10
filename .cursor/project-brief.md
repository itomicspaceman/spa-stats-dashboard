# Squash Dashboard Rebuild - Project Brief

## Mission
Replace the current Zoho Analytics dashboard ($400/year) with a custom Laravel 12 + Chart.js + MapLibre GL solution that provides identical functionality for the Squash Players country statistics dashboard at https://squash.players.app/squash-venues-courts-country-stats/

## Timeline
**Critical Deadline**: 5 days from project start (Zoho subscription renewal)
- Must be fully functional and deployed before renewal date
- No dependency on Zoho in production (API used only for validation during development)

## Scope

### In Scope
- Interactive world map showing squash venue locations with markers
- Country-level statistics (venues, courts, glass/non-glass breakdown)
- Multiple chart types: bar charts, pie charts, line charts, scatter plots
- Time-series data (venue growth over time)
- Filterable/sortable data tables
- Responsive design (mobile/tablet/desktop)
- 3-hour automated data sync from remote MariaDB
- RESTful API endpoints for frontend consumption
- Caching layer for performance

### Out of Scope (Phase 2 - Optional)
- WordPress plugin integration
- User authentication/authorization
- Real-time data updates
- User-facing data filters (continent, approval status)
- CSV export functionality
- Usage analytics (Plausible/GA)

## Tech Stack

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.3/8.4
- **Database**: Remote MariaDB 10.6 (read-only connection)
- **Caching**: Laravel file cache (Redis optional)
- **Scheduling**: Laravel scheduler + cron

### Frontend
- **Layout**: Bootstrap 5
- **Charts**: Chart.js 4.x
- **Maps**: MapLibre GL JS 4.x
- **HTTP Client**: Axios
- **Build Tool**: Vite

### Infrastructure
- **Local Dev**: Laravel Herd on Windows 11 Pro (`spa.test`)
- **Production**: cPanel with PHP 8.3+ selector
- **Domain**: `stats.squash.players.app`

### External Services
- **Map Tiles**: MapTiler (free tier, <100k loads/month)
- **Data Source**: `atlas.itomic.com` MariaDB (`squahliv_db`)

## Success Criteria

1. **Feature Parity**: All Zoho dashboard widgets replicated with identical or better UX
2. **Performance**: API responses <200ms with caching, page load <2s
3. **Reliability**: 3-hour sync runs successfully via cron, graceful degradation if DB unavailable
4. **Maintainability**: Clean, documented code following Laravel conventions
5. **Cost Savings**: Zero recurring costs (vs $400/year Zoho)
6. **Deployment**: Successfully deployed to production before Zoho renewal deadline

## Key Stakeholders
- **Users**: Squash players worldwide viewing venue/court statistics
- **Data Source**: Squash Players mobile app feeding MariaDB
- **Hosting**: squash.players.app cPanel account

## Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Zoho renewal deadline missed | High | Aggressive timeline, daily progress checks |
| Remote DB connection issues | Medium | Implement stale cache fallback, connection retry logic |
| MapTiler free tier limits | Low | Monitor usage, prepare OSM raster fallback |
| cPanel deployment complexity | Medium | Document deployment steps, test on staging first |
| Data aggregation performance | Medium | Optimize queries, use database indexes, cache aggressively |

## Constraints
- Read-only database access (no schema modifications)
- Must work with existing MariaDB schema (7,759 venues, 248 countries)
- No breaking changes to data source
- Must support existing WordPress embed pattern (iframe)

