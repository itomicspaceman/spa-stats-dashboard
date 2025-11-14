# Current Work: Context-Aware Loneliest Courts Map

## Status: Testing Phase

### What We Just Completed

We've successfully implemented a context-aware "Loneliest Squash Courts" map that adapts based on the geographic filter:

1. **Backend Changes (`app/Services/SquashDataAggregator.php`)**:
   - Refactored `loneliestCourts()` to accept `$filter` and `$limit` parameters
   - Added new protected method `loneliestCourtsInArea()` to handle country/state-specific queries
   - Logic now works as follows:
     - **World/Continent/Region views**: Returns loneliest venue per country (filtered by geography)
     - **Country view**: Returns top 20 loneliest venues within that country
     - **State view**: Returns top 10 loneliest venues within that state/county

2. **API Changes (`app/Http/Controllers/Api/SquashStatsController.php`)**:
   - Updated `loneliestCourts()` endpoint to accept `filter` query parameter
   - Dynamically sets limit based on filter type:
     - Country: 20 venues
     - State: 10 venues
     - World/Continent/Region: 250 (to cover all countries)
   - Cache keys now include filter and limit for proper caching

3. **Frontend Component (`resources/views/components/charts/loneliest-courts.blade.php`)**:
   - Created new dashboard component (adapted from trivia version)
   - Includes dynamic title and description elements with IDs for JavaScript updates
   - Map container, legend, and venue count badge

4. **Dashboard Integration**:
   - Added component to `resources/views/dashboards/world.blade.php` (after venue-map)
   - Added component to `resources/views/dashboards/country.blade.php` (after venue-map)

5. **Frontend JavaScript (`resources/js/dashboard.js`)**:
   - Modified `initLoneliestCourtsMap()` to:
     - Get current filter from URL using `getFilterParams()`
     - Pass filter to `fetchData('/loneliest-courts', filter)`
     - Dynamically update title and description based on filter type:
       - Country: "Top N Loneliest Squash Courts" + "in this country"
       - State: "Top N Loneliest Squash Courts" + "in this state/county"
       - Continent: "Loneliest Squash Courts per Country" + "within this continent"
       - Region: "Loneliest Squash Courts per Country" + "within this region"
       - World: Default titles

6. **Assets Rebuilt**:
   - Ran `npm run build` to compile JavaScript changes

### What Needs Testing

We need to verify the map works correctly in all filter contexts:

1. ✅ **World view** (`https://spa.test/`): Should show loneliest venue per country globally
2. ⏳ **Continent view** (e.g., `https://spa.test/?filter=continent:X`): Should show loneliest venue per country in that continent
3. ⏳ **Region view** (e.g., `https://spa.test/?filter=region:X`): Should show loneliest venue per country in that region
4. ⏳ **Country view** (e.g., `https://spa.test/?filter=country:AU`): Should show top 20 loneliest venues in that country
5. ⏳ **State view** (e.g., `https://spa.test/?filter=state:X`): Should show top 10 loneliest venues in that state

### Current Testing Status

- Currently viewing world view at `https://spa.test/`
- Need to scroll down to see the loneliest courts map (it appears after the venue map)
- After confirming world view works, need to test each filter type

### How to Continue Testing

1. **World View**: Scroll down on `https://spa.test/` to see the map, verify it shows "Loneliest Squash Courts per Country" with multiple countries
2. **Continent View**: Click on a continent in the continental breakdown chart, verify map updates to show only countries in that continent
3. **Region View**: Click on a region, verify map shows only countries in that region
4. **Country View**: Click on a country (e.g., Australia), verify map shows "Top 20 Loneliest Squash Courts" with venues only in that country
5. **State View**: Click on a state/county, verify map shows "Top 10 Loneliest Squash Courts" with venues only in that state

### Known Issues

- None at this stage - implementation is complete and awaiting testing

### Files Modified in This Session

- `app/Services/SquashDataAggregator.php` - Added filter support and `loneliestCourtsInArea()` method
- `app/Http/Controllers/Api/SquashStatsController.php` - Added filter parameter handling
- `resources/views/components/charts/loneliest-courts.blade.php` - Created new component
- `resources/views/dashboards/world.blade.php` - Added loneliest courts component
- `resources/views/dashboards/country.blade.php` - Added loneliest courts component
- `resources/js/dashboard.js` - Updated `initLoneliestCourtsMap()` for filter awareness
- `public/build/*` - Rebuilt assets

### Next Steps After Testing

1. If all filter contexts work correctly, commit changes to `develop` branch
2. Merge to `main` branch for production deployment
3. Verify automatic deployment to production site (`https://stats.squashplayers.app/`)

### Deployment Notes

- Production site auto-deploys via GitHub webhook when changes are pushed to `main` branch
- Webhook triggers `/home/stats/webhook-deploy.php` on `atlas.itomic.com`
- This runs `/home/stats/deploy.sh` which:
  - Pulls latest changes from GitHub
  - Syncs to `/home/stats/current`
  - Runs `npm install` and `npm run build` with Node 24
  - Sets correct permissions
  - Clears Laravel bootstrap cache

### Database Schema Reference

The `venues` table on the remote database (`squahliv_db`) includes:
- `nearest_venue_id` - ID of the nearest venue
- `nearest_venue_km` - Distance to nearest venue in kilometers
- `latitude`, `longitude` - Coordinates for mapping
- `country_id`, `state_id` - Geographic identifiers

The queries join with `countries`, `regions`, and `continents` tables to support geographic filtering.

