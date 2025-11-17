# System Patterns: Squash Court Stats

## Architecture Overview

### Service-Oriented Architecture
The application uses a service layer pattern with clear separation of concerns:

```
Controllers → Services → Models → Database
```

### Key Services

1. **SquashDataAggregator** - Data aggregation and caching
2. **GooglePlacesService** - Google Places API integration
3. **GooglePlacesTypeMapper** - Type mapping with confidence levels
4. **OpenAICategorizer** - AI-powered categorization
5. **VenueCategorizer** - Orchestration of categorization workflow
6. **VenueContextAnalyzer** - Detects sub-venue relationships
7. **CourtCountSearcher** - Web search for court counts
8. **CourtCountAnalyzer** - AI analysis of search results
9. **CourtCountUpdater** - Updates court counts with audit logging
10. **VenueCategoryUpdater** - Category updates with audit logging

### Design Patterns

#### 1. Service Layer Pattern
Business logic lives in services, not controllers:
```php
// ✅ GOOD: Service handles logic
$categorizer = app(VenueCategorizer::class);
$result = $categorizer->categorizeVenue($venue);

// ❌ BAD: Logic in controller
$venue->category_id = $this->determineCategory($venue);
```

#### 2. Repository Pattern (Implicit)
Eloquent models act as repositories with query scopes:
```php
Venue::active()->withCoordinates()->get();
```

#### 3. Strategy Pattern
Different categorization strategies (Google Mapping, AI, Name Analysis):
```php
if ($confidence === 'HIGH') {
    // Use Google mapping
} elseif ($confidence === 'MEDIUM') {
    // Use AI
} else {
    // Use name analysis
}
```

#### 4. Factory Pattern
Service instantiation with dependency injection:
```php
$categorizer = app(VenueCategorizer::class, [
    'courtCountSearcher' => app(CourtCountSearcher::class),
]);
```

## Data Flow Patterns

### Dashboard Data Flow
```
Scheduled Sync (3 hours)
  ↓
SquashDataAggregator
  ↓
Laravel Cache (10,800 seconds)
  ↓
API Endpoints
  ↓
Frontend (Chart.js, MapLibre)
```

### Categorization Flow
```
Venue (uncategorized)
  ↓
GooglePlacesService → Place Details
  ↓
GooglePlacesTypeMapper → Category + Confidence
  ↓
[If LOW confidence] → OpenAICategorizer
  ↓
VenueCategoryUpdater → Database + Audit Log
```

### Court Count Discovery Flow
```
Venue (no_of_courts = 0)
  ↓
CourtCountSearcher → Web Search Results
  ↓
CourtCountAnalyzer (OpenAI) → Extracted Count + Confidence
  ↓
CourtCountUpdater → Database + Audit Log
```

## Component Relationships

### Laravel Application
- **Models**: Venue, Country, State, Region, VenueCategory, VenueStatus
- **Controllers**: ChartController, GeographicAreasController
- **Services**: Data aggregation, categorization, court counting
- **Commands**: CategorizeVenues, Sync data
- **Routes**: Web routes for dashboards, API routes for data

### WordPress Plugin
- **Main File**: `squash-court-stats.php`
- **Shortcode**: `[squash_court_stats]` with parameters
- **Integration**: iframe-based for complete isolation
- **Auto-Updates**: GitHub releases via custom updater

### Deployment
- **Repository**: GitHub (itomic/squash-court-stats)
- **Production**: stats.squashplayers.app (atlas.itomic.com)
- **Deployment**: Automated via GitHub webhooks
- **Script**: `deploy.sh` handles full deployment workflow

## Key Technical Decisions

1. **iframe Integration** - Complete isolation between WordPress and Laravel
2. **Caching Strategy** - 3-hour cache matching Zoho refresh rate
3. **AI Usage** - Cost-effective (gpt-4o-mini for court counts, caching for translations)
4. **Audit Logging** - Custom tables (venue_category_updates, venue_court_count_updates)
5. **Single Shortcode** - WordPress best practice, unified API
6. **Service-Based** - Clear separation, testable, maintainable

