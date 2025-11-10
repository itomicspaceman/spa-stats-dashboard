# Coding Standards

## Core Principles

### 1. Native Functionality First
**Always favor framework-native solutions over custom implementations.**

- Use Laravel's built-in features before writing custom code
- Leverage Eloquent ORM over raw SQL queries
- Use Blade components over inline HTML
- Employ Laravel validation rules over custom validators
- Utilize Laravel's caching, scheduling, and queue systems

**Examples:**
```php
// ✅ GOOD: Native Eloquent with scopes
Venue::approved()->withCoordinates()->get();

// ❌ BAD: Raw SQL
DB::select("SELECT * FROM venues WHERE status = '1' AND latitude IS NOT NULL");

// ✅ GOOD: Laravel validation
$request->validate(['limit' => 'integer|min:1|max:100']);

// ❌ BAD: Custom validation
if (!is_int($limit) || $limit < 1 || $limit > 100) { throw new Exception(); }
```

### 2. DRY (Don't Repeat Yourself)
**Extract reusable logic; avoid copy-paste.**

- Create query scopes for repeated database conditions
- Use services for business logic shared across controllers
- Extract Blade components for repeated UI patterns
- Utilize traits for shared model behavior

**Examples:**
```php
// ✅ GOOD: Reusable scope
class Venue extends Model {
    public function scopeApproved($query) {
        return $query->where('status', '1');
    }
}

// ❌ BAD: Repeated condition everywhere
Venue::where('status', '1')->get();
Venue::where('status', '1')->count();
Venue::where('status', '1')->sum('no_of_courts');
```

### 3. KISS (Keep It Simple, Stupid)
**The simplest solution that works is almost always the correct one.**

- Avoid premature optimization
- Don't add features "just in case"
- Prefer readable code over clever code
- Complexity is a code smell—step back and find a simpler approach

**Examples:**
```php
// ✅ GOOD: Simple and clear
return Cache::remember('squash:country_stats', 10800, function () {
    return $this->aggregator->countryStats();
});

// ❌ BAD: Over-engineered
return $this->cacheManager
    ->withTTL(config('cache.squash.ttl'))
    ->withKey($this->keyGenerator->generate('country_stats'))
    ->remember(fn() => $this->aggregator->countryStats());
```

### 4. LESS IS MORE
**If you're adding layers of custom code, you're probably doing it wrong.**

- Question every new class/method/abstraction
- Delete unused code immediately
- Avoid unnecessary middleware, services, or helpers
- Let the framework do the heavy lifting

## Laravel-Specific Standards

### PSR-12 Compliance
- Follow PSR-12 coding style
- Use 4 spaces for indentation (no tabs)
- Opening braces on same line for classes/methods
- One blank line after namespace declaration

### Naming Conventions
```php
// Classes: PascalCase
class SquashDataAggregator {}

// Methods: camelCase
public function countryStats() {}

// Variables: camelCase
$venueCount = 100;

// Constants: UPPER_SNAKE_CASE
const MAX_RESULTS = 100;

// Database tables: snake_case plural
venues, countries, venue_categories

// Model properties: snake_case (match DB)
$venue->no_of_courts
```

### Type Hints & Return Types
```php
// ✅ GOOD: Full type declarations
public function topCountries(string $metric, int $limit = 30): Collection
{
    return Country::orderBy($metric, 'desc')->limit($limit)->get();
}

// ❌ BAD: No type hints
public function topCountries($metric, $limit = 30)
{
    return Country::orderBy($metric, 'desc')->limit($limit)->get();
}
```

### Eloquent Best Practices
```php
// ✅ GOOD: Eloquent relationships
$venue->country->name

// ❌ BAD: Manual joins everywhere
DB::table('venues')->join('countries', ...)->get()

// ✅ GOOD: Query scopes
Venue::approved()->withCoordinates()->get()

// ❌ BAD: Repeated where clauses
Venue::where('status', '1')->whereNotNull('latitude')->get()

// ✅ GOOD: Attribute casting in model
protected $casts = ['created_at' => 'datetime'];

// ❌ BAD: Manual date parsing
Carbon::parse($venue->created_at)
```

### Controller Guidelines
- Keep controllers thin—delegate to services
- Use resource controllers for CRUD operations
- Return JSON via Laravel's response helpers
- Validate input using Form Requests or inline validation

```php
// ✅ GOOD: Thin controller
public function countryStats(SquashDataAggregator $aggregator)
{
    return response()->json(
        Cache::remember('squash:country_stats', 10800, 
            fn() => $aggregator->countryStats()
        )
    );
}

// ❌ BAD: Fat controller with business logic
public function countryStats()
{
    $venues = DB::connection('squash_remote')->table('venues')...
    // 50 lines of aggregation logic
    return response()->json($result);
}
```

### Service Layer
- Services contain business logic and data aggregation
- Services are injected via dependency injection
- Services return plain arrays/collections, not responses
- Keep services focused (Single Responsibility Principle)

```php
// ✅ GOOD: Focused service
class SquashDataAggregator
{
    public function countryStats(): array
    {
        return Venue::approved()
            ->with('country')
            ->get()
            ->groupBy('country_id')
            ->map(fn($venues) => [
                'venues' => $venues->count(),
                'courts' => $venues->sum('no_of_courts'),
            ])
            ->toArray();
    }
}
```

### Caching Patterns
```php
// ✅ GOOD: Cache::remember with closure
Cache::remember('key', $ttl, fn() => $expensiveOperation());

// ✅ GOOD: Cache tags for grouped invalidation (if using Redis)
Cache::tags(['squash', 'stats'])->remember('key', $ttl, $callback);

// ❌ BAD: Manual cache checking
if (Cache::has('key')) {
    return Cache::get('key');
}
$data = $expensiveOperation();
Cache::put('key', $data, $ttl);
return $data;
```

### Error Handling
```php
// ✅ GOOD: Let Laravel handle exceptions
public function show(int $id)
{
    return Venue::findOrFail($id); // Throws 404 automatically
}

// ❌ BAD: Manual error handling
public function show(int $id)
{
    $venue = Venue::find($id);
    if (!$venue) {
        return response()->json(['error' => 'Not found'], 404);
    }
    return $venue;
}
```

## Frontend Standards

### JavaScript/Vue/Blade
- Use modern ES6+ syntax
- Prefer `const` over `let`, never use `var`
- Use arrow functions for callbacks
- Async/await over promise chains
- Destructure objects where appropriate

```javascript
// ✅ GOOD: Modern, clean
const fetchCountryStats = async () => {
    try {
        const { data } = await axios.get('/api/squash/country-stats');
        renderChart(data);
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
};

// ❌ BAD: Old-style promises
function fetchCountryStats() {
    axios.get('/api/squash/country-stats')
        .then(function(response) {
            renderChart(response.data);
        })
        .catch(function(error) {
            console.error('Failed to load stats:', error);
        });
}
```

### Chart.js Configuration
- Extract chart configs to separate objects
- Use responsive options
- Provide accessibility labels

### MapLibre GL
- Use native clustering when >100 markers
- Leverage GeoJSON for data binding
- Implement proper popup templates

## Testing Standards

### PHPUnit Tests
- Test public methods only
- Use factories for test data
- Mock external services (remote DB in tests)
- Aim for >80% coverage on services

```php
// ✅ GOOD: Clear, focused test
public function test_country_stats_returns_valid_structure()
{
    $stats = $this->aggregator->countryStats();
    
    $this->assertIsArray($stats);
    $this->assertArrayHasKey('total_countries', $stats);
    $this->assertIsInt($stats['total_countries']);
}
```

## Documentation Standards

### Code Comments
- Write self-documenting code (clear names > comments)
- Add comments for "why", not "what"
- Use PHPDoc blocks for public methods
- Document complex algorithms or business rules

```php
// ✅ GOOD: Explains why
// We exclude status '3' (flagged for deletion) to match Zoho behavior
$query->whereNotIn('status', ['3', '4']);

// ❌ BAD: States the obvious
// Get all venues
$venues = Venue::all();
```

### API Documentation
- Document all endpoints in technical-architecture.md
- Include request/response examples
- Note validation rules and error codes

## OOP Principles Hierarchy

**From user memory: Prioritized framework for ALL programming decisions**

1. **PSR Compliance + Automated Tests FIRST** - Catch problems quickest
2. **SOLID at Class/Module Boundaries** - SRP + DIP for testability
3. **DRY/KISS/YAGNI for Daily Coding** - Maintain velocity, fight complexity
4. **Architectural Patterns ONLY When Domain Demands** - Repository/CQRS/DDD only when simple services insufficient

**Balance and context—not any single acronym—make for sustainable, artisan-grade code.**

## Git Commit Standards

- Use present tense ("Add feature" not "Added feature")
- Keep first line under 50 characters
- Reference issue numbers if applicable
- Commit logical units of work

```
Add country stats aggregation service

- Implement countryStats() method with Eloquent
- Add caching layer with 3-hour TTL
- Include PHPUnit tests for data structure
```

## Performance Guidelines

- Use database indexes (already present in remote DB)
- Eager load relationships to avoid N+1 queries
- Cache expensive operations
- Use `select()` to limit columns retrieved
- Paginate large result sets

## Security Checklist

- ✅ Validate all user input
- ✅ Use parameterized queries (Eloquent does this)
- ✅ Never expose `.env` or credentials
- ✅ Set `APP_DEBUG=false` in production
- ✅ Use HTTPS in production
- ✅ Implement rate limiting on API routes
- ✅ Sanitize output (JSON encoding handles this)
- ✅ Read-only database user (already configured)

