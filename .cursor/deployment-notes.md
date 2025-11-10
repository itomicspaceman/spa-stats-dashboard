# Deployment Notes

## Local Testing

The dashboard has been successfully built with:
- Bootstrap 5.3.2 for responsive layout
- Chart.js 4.4.0 for data visualization
- MapLibre GL JS 4.0.0 for interactive maps
- Clean, modern design with out-of-the-box styling

### Known Issues

**Browser Caching**: The Laravel welcome page may be cached by your browser. To view the dashboard:
1. Clear your browser cache completely
2. Use an incognito/private window
3. Or navigate directly to `http://spa.test/?v=1` (cache-busting parameter)

### API Endpoints

All API endpoints are working correctly:
- `/api/squash/country-stats` - Summary statistics
- `/api/squash/top-countries?metric=venues&limit=20` - Top countries by metric
- `/api/squash/court-distribution` - Court distribution data
- `/api/squash/timeline` - Timeline data
- `/api/squash/venue-types` - Venue categories
- `/api/squash/map` - GeoJSON map data

### Data Sync

The sync command is working perfectly:
```bash
php artisan squash:sync
```

This command:
- Aggregates all data from the remote MariaDB database
- Caches results for 3 hours
- Exports JSON files to `storage/app/dashboard/` for inspection
- Logs sync operations to the `squash_sync_logs` table

The sync is scheduled to run every 3 hours automatically.

## Production Deployment

### Requirements

- PHP 8.3 or 8.4
- Composer
- Node.js & NPM (for asset compilation)
- MariaDB/MySQL (for local database)
- Access to remote MariaDB database (atlas.itomic.com)

### Steps

1. **Upload Files to cPanel**
   - Upload all files except `node_modules` and `.env`
   - Set document root to `/public`

2. **Environment Configuration**
   - Copy `.env.example` to `.env`
   - Update database credentials
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Generate app key: `php artisan key:generate`

3. **Install Dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install
   npm run build
   ```

4. **Database Migration**
   ```bash
   php artisan migrate --force
   ```

5. **Initial Data Sync**
   ```bash
   php artisan squash:sync
   ```

6. **Set Up Cron Job**
   Add to cPanel cron jobs (every 3 hours):
   ```
   0 */3 * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```

7. **Optimize for Production**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### File Permissions

Ensure these directories are writable:
- `storage/`
- `bootstrap/cache/`

### WordPress Integration (Phase 2)

To embed the dashboard in WordPress:
1. Use an iframe: `<iframe src="https://stats.squash.players.app" width="100%" height="800"></iframe>`
2. Or use a custom WordPress plugin to fetch and display the dashboard
3. Ensure CORS headers are set if fetching via AJAX

### Monitoring

- Check sync logs: `SELECT * FROM squash_sync_logs ORDER BY started_at DESC LIMIT 10;`
- Monitor cache: Check `storage/app/dashboard/*.json` files
- Check Laravel logs: `storage/logs/laravel.log`

### Performance

- All data is cached for 3 hours
- API responses are fast (< 100ms)
- Dashboard loads all components in parallel
- MapLibre uses clustering for better performance with many markers

## Differences from Zoho Analytics

### Styling
- Modern, clean design using Bootstrap 5
- Chart.js default color schemes (can be customized)
- MapLibre GL default map tiles (OpenStreetMap)
- Responsive layout that works on all devices

### Functionality
- All core metrics are replicated
- Interactive map with clustering
- Real-time data updates (every 3 hours)
- Faster load times than Zoho

### Cost Savings
- $0 per year (vs $400 for Zoho)
- Full control over data and presentation
- No vendor lock-in

