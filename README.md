# Squash Court Stats

A comprehensive Laravel 12 dashboard displaying global squash venue and court statistics with interactive maps and data visualizations.

## Features

- **Interactive Global Map**: MapLibre GL JS map showing 6,600+ squash venues worldwide with clustering
- **Real-time Statistics**: Summary cards showing total countries, venues, and courts
- **Data Visualizations**: 
  - Continental and sub-continental breakdowns
  - Top 20 countries by venues, courts, and outdoor courts
  - Venue categories distribution
  - Courts per venue distribution
  - Website statistics
  - Timeline of venue additions
- **Responsive Design**: Bootstrap 5 with mobile-friendly interface
- **API Endpoints**: RESTful API for all statistics and map data
- **Performance Optimized**: Caching, lazy loading, and optimized queries

## Tech Stack

- **Backend**: Laravel 12, PHP 8.3
- **Frontend**: Vite, Chart.js, MapLibre GL JS
- **Database**: MySQL (remote connection to squahliv_db)
- **Styling**: Bootstrap 5, Font Awesome 6
- **Maps**: MapLibre GL JS with OpenStreetMap tiles
- **Charts**: Chart.js with DataLabels plugin

## Installation

### Requirements

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL database access

### Setup

1. Clone the repository:
```bash
git clone https://github.com/itomic/squash-court-stats.git
cd squash-court-stats
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node dependencies:
```bash
npm install
```

4. Copy environment file and configure:
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure database connection in `.env`:
```env
SQUASH_DB_HOST=your_host
SQUASH_DB_PORT=3306
SQUASH_DB_DATABASE=squahliv_db
SQUASH_DB_USERNAME=your_username
SQUASH_DB_PASSWORD=your_password
```

6. Build frontend assets:
```bash
npm run build
```

7. Optimize for production:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Development

Run development server:
```bash
npm run dev
php artisan serve
```

## API Endpoints

All endpoints are prefixed with `/api/squash`:

- `GET /country-stats` - Summary statistics
- `GET /map` - GeoJSON map data with venue locations
- `GET /top-countries?metric={metric}&limit={limit}` - Top countries by metric
- `GET /court-distribution` - Courts per venue distribution
- `GET /court-types` - Court types breakdown
- `GET /categories` - Venue categories
- `GET /regional-breakdown` - Continental breakdown
- `GET /subcontinental-breakdown` - Sub-continental breakdown
- `GET /timeline?period={period}` - Venues added over time
- `GET /website-stats` - Venues with websites statistics

## Deployment

Deployed to: `stats.squash.players.app`

## License

Proprietary - © 2025 Itomic Apps

## About

Part of the Squash Players App ecosystem by [Itomic Apps](https://www.itomic.com.au)
