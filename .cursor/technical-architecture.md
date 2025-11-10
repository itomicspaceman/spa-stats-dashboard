# Technical Architecture

## System Overview

```
┌─────────────────┐         ┌──────────────────┐         ┌─────────────────┐
│  Remote MariaDB │ ◄─────► │  Laravel Backend │ ◄─────► │  Frontend (JS)  │
│ atlas.itomic.com│  Read   │   spa.test /     │   JSON  │  Chart.js +     │
│  squahliv_db    │  Only   │ stats.squash...  │   API   │  MapLibre GL    │
└─────────────────┘         └──────────────────┘         └─────────────────┘
                                     │
                                     ▼
                            ┌─────────────────┐
                            │  File Cache     │
                            │  (3hr TTL)      │
                            └─────────────────┘
```

## Database Schema

### Remote Connection (squash_remote)
- **Host**: atlas.itomic.com
- **Database**: squahliv_db
- **User**: squahliv_cursor
- **Password**: ENV:SQUASH_DB_PASSWORD
- **Access**: SELECT only

### Key Tables

#### venues (7,759 rows)
```sql
CREATE TABLE `venues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `latitude` decimal(13,10) DEFAULT NULL,
  `longitude` decimal(13,10) DEFAULT NULL,
  `elevation` double DEFAULT NULL,
  `country_id` int(10) unsigned DEFAULT NULL,
  `state_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT 6,
  `status` enum('0','1','2','3','4') DEFAULT NULL,
  -- 0=Pending, 1=Approved, 2=Rejected, 3=FlaggedForDeletion, 4=Deleted
  `no_of_courts` int(10) unsigned NOT NULL DEFAULT 0,
  `no_of_glass_courts` int(10) unsigned DEFAULT NULL,
  `no_of_non_glass_courts` int(10) unsigned DEFAULT NULL,
  `no_of_outdoor_courts` int(10) unsigned DEFAULT NULL,
  `no_of_doubles_courts` int(10) unsigned DEFAULT NULL,
  `no_of_singles_courts` int(10) unsigned DEFAULT NULL,
  `no_of_hardball_doubles_courts` int(10) unsigned DEFAULT NULL,
  `g_place_id` varchar(191) NOT NULL UNIQUE,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_venues_status_country` (`status`,`country_id`),
  KEY `idx_venues_coordinates` (`latitude`,`longitude`)
) ENGINE=InnoDB;
```

#### countries (248 rows)
```sql
CREATE TABLE `countries` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `alpha_2_code` varchar(191) DEFAULT NULL,
  `alpha_3_code` varchar(191) DEFAULT NULL,
  `venues_count` int(11) DEFAULT NULL,
  `population` int(11) DEFAULT NULL,
  `landarea` int(11) NOT NULL COMMENT 'In km2',
  `region_id` int(11) DEFAULT NULL,
  `center_lat` decimal(11,8) DEFAULT NULL,
  `center_lng` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_countries_region` (`region_id`)
) ENGINE=InnoDB;
```

#### states (1,017 rows)
Basic state/province data with country relationships

#### regions (22 rows)
Geographic regions for grouping countries

## API Endpoints

### Base URL
- Local: `http://spa.test/api/squash`
- Production: `https://stats.squash.players.app/api/squash`

### Endpoints

#### GET /country-stats
Returns comprehensive country-level statistics
```json
{
  "total_countries": 248,
  "countries_with_venues": 87,
  "total_venues": 7759,
  "total_courts": 15234,
  "by_country": [
    {
      "id": 1,
      "name": "England",
      "alpha_2": "GB",
      "venues": 1234,
      "courts": 2456,
      "glass_courts": 1200,
      "non_glass_courts": 1256,
      "outdoor_courts": 12,
      "venues_per_million": 23.4
    }
  ]
}
```

#### GET /top-countries?metric=venues&limit=30
Returns ranked list of countries by specified metric
- **Params**: `metric` (venues|courts|glass_courts), `limit` (default 30)

#### GET /court-distribution
Pie chart data showing distribution of court counts per venue
```json
{
  "labels": ["1 court", "2 courts", "3 courts", "4+ courts"],
  "data": [2345, 3456, 1234, 724]
}
```

#### GET /timeline?interval=monthly
Time-series data for venue growth
- **Params**: `interval` (daily|weekly|monthly|yearly)

#### GET /venue-types
Breakdown by venue category and membership model

#### GET /map
GeoJSON FeatureCollection for MapLibre
```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Point",
        "coordinates": [-0.1276, 51.5074]
      },
      "properties": {
        "id": 123,
        "name": "Example Squash Club",
        "courts": 5,
        "glass_courts": 3,
        "country": "England"
      }
    }
  ]
}
```

## Caching Strategy

### Cache Keys
- `squash:country_stats` - Full country statistics
- `squash:top_countries:{metric}:{limit}` - Ranked lists
- `squash:court_distribution` - Distribution data
- `squash:timeline:{interval}` - Time-series data
- `squash:venue_types` - Category breakdown
- `squash:map_data` - GeoJSON for map

### Cache TTL
- **Primary**: 3 hours (matches sync schedule)
- **Fallback**: Stale cache served if DB unavailable

### Cache Warming
- Artisan command `squash:sync` runs every 3 hours via cron
- Manual trigger via `/api/squash/sync?token=ENV:SYNC_TOKEN`

## Data Aggregation Queries

### Example: Country Stats
```php
DB::connection('squash_remote')
    ->table('venues')
    ->join('countries', 'venues.country_id', '=', 'countries.id')
    ->where('venues.status', '1') // Approved only
    ->select([
        'countries.id',
        'countries.name',
        'countries.alpha_2_code',
        DB::raw('COUNT(venues.id) as venues'),
        DB::raw('SUM(venues.no_of_courts) as courts'),
        DB::raw('SUM(venues.no_of_glass_courts) as glass_courts'),
        DB::raw('SUM(venues.no_of_non_glass_courts) as non_glass_courts'),
    ])
    ->groupBy('countries.id')
    ->orderBy('venues', 'desc')
    ->get();
```

## External Service Configuration

### MapTiler
- **API Key**: ENV:MAPTILER_KEY
- **Tile URL**: `https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key={key}`
- **Free Tier**: 100,000 map loads/month
- **Fallback**: OpenStreetMap raster tiles if limit exceeded

### Zoho Analytics (Development Only)
- **Client ID**: ENV:ZOHO_CLIENT_ID
- **Client Secret**: ENV:ZOHO_CLIENT_SECRET
- **Refresh Token**: ENV:ZOHO_REFRESH_TOKEN
- **Workspace ID**: 2371467000000013001
- **Usage**: Validation/comparison only, not used in production

## Security Considerations

1. **Database**: Read-only credentials, no write operations possible
2. **API**: Rate limiting (60 requests/minute per IP)
3. **CORS**: Configured for squash.players.app domain
4. **Secrets**: All credentials in `.env`, never committed
5. **Input Validation**: Laravel validation rules on all query params
6. **Output Sanitization**: JSON encoding, no raw HTML output

## Performance Targets

- **API Response Time**: <200ms (with cache)
- **Page Load**: <2s (including map tiles)
- **Database Queries**: <500ms (without cache)
- **Sync Duration**: <60s for full aggregation
- **Map Markers**: Clustered above 100 venues per viewport

## Deployment Architecture

### Local (Herd)
- Domain: `spa.test`
- PHP: 8.3 via Herd
- Database: Remote connection to atlas.itomic.com
- Cache: File-based in `storage/framework/cache`

### Production (cPanel)
- Domain: `stats.squash.players.app`
- PHP: 8.3 via cPanel PHP Selector
- Document Root: `public/`
- Cron: `*/10 * * * * cd /home/user/stats && php artisan schedule:run`
- SSL: Let's Encrypt via cPanel AutoSSL

