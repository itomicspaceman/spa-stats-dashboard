<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Region;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

class SquashDataAggregator
{
    /**
     * Apply geographic filter to a query builder.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string|null $filter Format: "type:code" (e.g., "country:US", "continent:5", "region:10", "state:810")
     * @param bool $hasRegionsJoin Whether the query already has a regions join
     * @param bool $hasContinentsJoin Whether the query already has a continents join
     * @return \Illuminate\Database\Query\Builder
     */
    protected function applyGeographicFilter($query, ?string $filter, bool $hasRegionsJoin = false, bool $hasContinentsJoin = false)
    {
        if (!$filter) {
            return $query;
        }

        // Parse filter format: "type:code"
        $parts = explode(':', $filter, 2);
        if (count($parts) !== 2) {
            return $query;
        }

        [$type, $code] = $parts;

        switch ($type) {
            case 'continent':
                // Filter by continent ID - add joins only if not already present
                if (!$hasRegionsJoin) {
                    $query->join('regions', 'countries.region_id', '=', 'regions.id');
                }
                if (!$hasContinentsJoin) {
                    $query->join('continents', 'regions.continent_id', '=', 'continents.id');
                }
                $query->where('continents.id', $code);
                break;

            case 'region':
                // Filter by region ID - add join only if not already present
                if (!$hasRegionsJoin) {
                    $query->join('regions', 'countries.region_id', '=', 'regions.id');
                }
                $query->where('regions.id', $code);
                break;

            case 'country':
                // Filter by country (alpha_2_code, alpha_3_code, or ID)
                if (is_numeric($code)) {
                    $query->where('countries.id', $code);
                } elseif (strlen($code) === 2) {
                    $query->where('countries.alpha_2_code', strtoupper($code));
                } elseif (strlen($code) === 3) {
                    $query->where('countries.alpha_3_code', strtoupper($code));
                }
                break;

            case 'state':
                // Filter by state ID
                $query->where('venues.state_id', $code);
                break;
        }

        return $query;
    }

    /**
     * Get comprehensive country-level statistics.
     *
     * @param string|null $filter Geographic filter (e.g., "country:US", "continent:5")
     * @return array
     */
    public function countryStats(?string $filter = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1'); // Approved only

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $stats = $query->select([
                'countries.id',
                'countries.name',
                'countries.alpha_2_code',
                'countries.alpha_3_code',
                'countries.population',
                'countries.landarea',
                DB::raw('COUNT(venues.id) as venues'),
                DB::raw('SUM(venues.no_of_courts) as courts'),
                DB::raw('SUM(venues.no_of_glass_courts) as glass_courts'),
                DB::raw('SUM(venues.no_of_non_glass_courts) as non_glass_courts'),
                DB::raw('SUM(venues.no_of_outdoor_courts) as outdoor_courts'),
                DB::raw('SUM(venues.no_of_doubles_courts) as doubles_courts'),
                DB::raw('SUM(venues.no_of_singles_courts) as singles_courts'),
                DB::raw('SUM(venues.no_of_hardball_doubles_courts) as hardball_doubles_courts'),
            ])
            ->groupBy('countries.id', 'countries.name', 'countries.alpha_2_code', 'countries.alpha_3_code', 'countries.population', 'countries.landarea')
            ->orderBy('venues', 'desc')
            ->get();

        // Calculate derived metrics
        $stats = $stats->map(function ($country) {
            $country->venues_per_million = $country->population > 0
                ? round(($country->venues / $country->population) * 1000000, 2)
                : 0;
            $country->courts_per_million = $country->population > 0
                ? round(($country->courts / $country->population) * 1000000, 2)
                : 0;
            return $country;
        });

        // Calculate summary stats based on filter
        if ($filter) {
            // When filtered, calculate from the filtered stats
            return [
                'total_countries' => $stats->count(),
                'countries_with_venues' => $stats->count(),
                'total_venues' => (int) $stats->sum('venues'),
                'total_courts' => (int) $stats->sum('courts'),
            ];
        } else {
            // Global stats
            return [
                'total_countries' => Country::count(),
                'countries_with_venues' => $stats->count(),
                'total_venues' => Venue::approved()->count(),
                'total_courts' => (int) Venue::approved()->sum('no_of_courts'),
            ];
        }
    }

    /**
     * Get top countries by specified metric.
     *
     * @param string $metric
     * @param int $limit
     * @param string|null $filter Geographic filter
     * @return array
     */
    public function topCountriesBy(string $metric = 'venues', int $limit = 30, ?string $filter = null): array
    {
        $validMetrics = ['venues', 'courts', 'glass_courts', 'outdoor_courts'];
        if (!in_array($metric, $validMetrics)) {
            $metric = 'venues';
        }

        $selectMap = [
            'venues' => ['expression' => 'COUNT(venues.id)', 'alias' => 'total_venues'],
            'courts' => ['expression' => 'SUM(venues.no_of_courts)', 'alias' => 'total_courts'],
            'glass_courts' => ['expression' => 'SUM(venues.no_of_glass_courts)', 'alias' => 'total_glass_courts'],
            'outdoor_courts' => ['expression' => 'SUM(venues.no_of_outdoor_courts)', 'alias' => 'total_outdoor_courts'],
        ];

        $alias = $selectMap[$metric]['alias'];
        $expression = $selectMap[$metric]['expression'];

        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1');

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $results = $query->select([
                'countries.name',
                'countries.alpha_2_code',
                DB::raw("{$expression} as {$alias}"),
            ])
            ->groupBy('countries.id', 'countries.name', 'countries.alpha_2_code')
            ->orderBy($alias, 'desc')
            ->limit($limit)
            ->get();

        return $results->toArray();
    }

    /**
     * Get court distribution (how many courts per venue).
     *
     * @param string|null $filter Geographic filter
     * @return array
     */
    public function courtDistribution(?string $filter = null): array
    {
        // Build query for approved venues
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1');

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $allVenues = $query->select('venues.no_of_courts', DB::raw('COUNT(*) as count'))
            ->groupBy('venues.no_of_courts')
            ->orderBy('venues.no_of_courts')
            ->get();

        // Initialize buckets for individual court counts (1-10, 11+, Unknown)
        $buckets = [
            'Unknown' => 0,
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            '5' => 0,
            '6' => 0,
            '7' => 0,
            '8' => 0,
            '9' => 0,
            '10' => 0,
            '11+' => 0,
        ];

        foreach ($allVenues as $item) {
            $courts = $item->no_of_courts;
            
            if ($courts === null || $courts === 0) {
                $buckets['Unknown'] += $item->count;
            } elseif ($courts >= 1 && $courts <= 10) {
                $buckets[(string)$courts] += $item->count;
            } else {
                $buckets['11+'] += $item->count;
            }
        }

        // Remove buckets with zero count for cleaner visualization
        $buckets = array_filter($buckets, function($count) {
            return $count > 0;
        });

        return [
            'labels' => array_keys($buckets),
            'data' => array_values($buckets),
        ];
    }

    /**
     * Get timeline data showing venue growth over time.
     *
     * @param string $interval
     * @param string|null $filter Geographic filter
     * @return array
     */
    public function timeline(string $interval = 'monthly', ?string $filter = null): array
    {
        $dateFormat = match ($interval) {
            'yearly' => '%Y',
            'monthly' => '%Y-%m',
            'weekly' => '%Y-%u',
            default => '%Y-%m',
        };

        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1')
            ->whereNotNull('venues.created_at');

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $timeline = $query->select(DB::raw("DATE_FORMAT(venues.created_at, '{$dateFormat}') as period"), DB::raw('COUNT(*) as count'))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $timeline->toArray();
    }

    /**
     * Get venue types breakdown (membership model, categories).
     *
     * @param string|null $filter Geographic filter
     * @return array
     */
    public function venueTypes(?string $filter = null): array
    {
        // Category breakdown
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('venue_categories', 'venues.category_id', '=', 'venue_categories.id')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1');

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $categories = $query->select('venue_categories.name', DB::raw('COUNT(*) as venue_count'))
            ->groupBy('venue_categories.id', 'venue_categories.name')
            ->orderBy('venue_count', 'desc')
            ->get();

        return $categories->toArray();
    }

    /**
     * Get map data as GeoJSON FeatureCollection.
     *
     * @param array $filters
     * @return array
     */
    /**
     * Get anonymized map points for visualization.
     * Does NOT include venue names, IDs, or addresses to prevent scraping.
     *
     * @param string|null $filter Geographic filter
     * @return array
     */
    public function mapPoints(?string $filter = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1')
            ->whereNotNull('venues.latitude')
            ->whereNotNull('venues.longitude')
            ->where('venues.latitude', '!=', 0)
            ->where('venues.longitude', '!=', 0);

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $venues = $query->select([
                'venues.id',
                'venues.name',
                'venues.physical_address',
                'venues.suburb',
                'venues.state',
                'venues.latitude',
                'venues.longitude',
                'venues.no_of_courts',
                'venues.no_of_glass_courts',
                'venues.no_of_outdoor_courts',
                'venues.telephone',
                'venues.website',
                'countries.name as country_name',
                'countries.alpha_2_code as country_code',
            ])
            ->get();

        $features = $venues->map(function ($venue) {
            // Build full address
            $addressParts = array_filter([
                $venue->physical_address,
                $venue->suburb,
                $venue->state,
            ]);
            $fullAddress = implode(', ', $addressParts);
            
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    // Coordinates are rounded to ~1km precision to prevent exact location scraping
                    'coordinates' => [
                        round((float) $venue->longitude, 2),
                        round((float) $venue->latitude, 2)
                    ],
                ],
                'properties' => [
                    'name' => $venue->name ?? 'Unknown',
                    'address' => $fullAddress ?: 'Address not available',
                    'courts' => $venue->no_of_courts ?? null,
                    'glass_courts' => $venue->no_of_glass_courts,
                    'outdoor_courts' => $venue->no_of_outdoor_courts,
                    'telephone' => $venue->telephone ?? null,
                    'website' => $venue->website ?? null,
                    'country' => $venue->country_name ?? 'Unknown',
                    'country_code' => $venue->country_code ?? null,
                    'suburb' => $venue->suburb,
                ],
            ];
        });

        return [
            'type' => 'FeatureCollection',
            'features' => $features->toArray(),
        ];
    }

    /**
     * Get regional breakdown of venues and courts.
     *
     * @param string|null $filter Geographic filter
     * @return array
     */
    public function regionalBreakdown(?string $filter = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->join('regions', 'countries.region_id', '=', 'regions.id')
            ->join('continents', 'regions.continent_id', '=', 'continents.id')
            ->where('venues.status', '1');

        // Apply geographic filter (but note: filtering by continent on a continent breakdown doesn't make much sense)
        // This is mainly for consistency and for potential region/country/state filters
        $query = $this->applyGeographicFilter($query, $filter, true, true);

        $continents = $query->select([
                'continents.id',
                'continents.name',
                DB::raw('COUNT(DISTINCT venues.id) as venues'),
                DB::raw('COUNT(DISTINCT countries.id) as countries'),
                DB::raw('SUM(venues.no_of_courts) as courts'),
                DB::raw('SUM(venues.no_of_glass_courts) as glass_courts'),
                DB::raw('SUM(venues.no_of_outdoor_courts) as outdoor_courts'),
            ])
            ->groupBy('continents.id', 'continents.name')
            ->orderBy('venues', 'desc')
            ->get();

        return $continents->toArray();
    }

    /**
     * Get sub-continental breakdown of venues and courts.
     *
     * @param string|null $filter Geographic filter
     * @return array
     */
    public function subContinentalBreakdown(?string $filter = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->join('regions', 'countries.region_id', '=', 'regions.id')
            ->where('venues.status', '1');

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter, true, false);

        $regions = $query->select([
                'regions.id',
                'regions.name',
                DB::raw('COUNT(DISTINCT venues.id) as venues'),
                DB::raw('COUNT(DISTINCT countries.id) as countries'),
                DB::raw('SUM(venues.no_of_courts) as courts'),
                DB::raw('SUM(venues.no_of_glass_courts) as glass_courts'),
                DB::raw('SUM(venues.no_of_outdoor_courts) as outdoor_courts'),
            ])
            ->groupBy('regions.id', 'regions.name')
            ->orderBy('venues', 'desc')
            ->get();

        return $regions->toArray();
    }

    /**
     * Get court types breakdown (glass vs non-glass, indoor vs outdoor).
     *
     * @return array
     */
    public function courtTypesBreakdown(): array
    {
        $totals = DB::connection('squash_remote')
            ->table('venues')
            ->where('status', '1')
            ->select([
                DB::raw('SUM(no_of_courts) as total_courts'),
                DB::raw('SUM(no_of_glass_courts) as glass_courts'),
                DB::raw('SUM(no_of_non_glass_courts) as non_glass_courts'),
                DB::raw('SUM(no_of_outdoor_courts) as outdoor_courts'),
                DB::raw('SUM(no_of_singles_courts) as singles_courts'),
                DB::raw('SUM(no_of_doubles_courts) as doubles_courts'),
                DB::raw('SUM(no_of_hardball_doubles_courts) as hardball_doubles_courts'),
            ])
            ->first();

        // Calculate indoor courts (total - outdoor)
        $indoorCourts = ($totals->total_courts ?? 0) - ($totals->outdoor_courts ?? 0);

        return [
            'total_courts' => $totals->total_courts ?? 0,
            'glass_courts' => $totals->glass_courts ?? 0,
            'non_glass_courts' => $totals->non_glass_courts ?? 0,
            'indoor_courts' => $indoorCourts,
            'outdoor_courts' => $totals->outdoor_courts ?? 0,
            'singles_courts' => $totals->singles_courts ?? 0,
            'doubles_courts' => $totals->doubles_courts ?? 0,
            'hardball_doubles_courts' => $totals->hardball_doubles_courts ?? 0,
        ];
    }

    /**
     * Get venues by state/province for a given country.
     *
     * @param int $countryId
     * @return array
     */
    public function venuesByState(int $countryId): array
    {
        $states = DB::connection('squash_remote')
            ->table('venues')
            ->join('states', 'venues.state_id', '=', 'states.id')
            ->where('venues.country_id', $countryId)
            ->where('venues.status', '1')
            ->select([
                'states.name',
                DB::raw('COUNT(*) as venues'),
                DB::raw('SUM(venues.no_of_courts) as courts'),
            ])
            ->groupBy('states.id', 'states.name')
            ->orderBy('venues', 'desc')
            ->get();

        return [
            'country_id' => $countryId,
            'data' => $states->toArray(),
        ];
    }

    /**
     * Get venues and courts breakdown by state/county (with filter support).
     *
     * @param string|null $filter
     * @return array
     */
    public function stateBreakdown(?string $filter = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('states', 'venues.state_id', '=', 'states.id')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1');

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $states = $query->select([
                'states.name',
                DB::raw('COUNT(*) as venues'),
                DB::raw('SUM(COALESCE(venues.no_of_courts, 0)) as courts'),
            ])
            ->groupBy('states.id', 'states.name')
            ->orderBy('venues', 'desc')
            ->get();

        return $states->toArray();
    }

    /**
     * Get top venues by number of courts.
     *
     * @param int $limit
     * @param string|null $filter
     * @return array
     */
    public function topVenuesByCourts(int $limit = 20, ?string $filter = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1')
            ->whereNotNull('venues.no_of_courts')
            ->where('venues.no_of_courts', '>', 0);

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $results = $query->select([
                'venues.name',
                'venues.physical_address',
                'venues.suburb',
                'venues.state',
                'venues.no_of_courts',
                'venues.g_place_id',
                'countries.name as country_name',
            ])
            ->orderBy('venues.no_of_courts', 'desc')
            ->limit($limit)
            ->get();

        return $results->toArray();
    }

    /**
     * Get top countries with multiple metrics for comparison.
     *
     * @param int $limit
     * @return array
     */
    public function topCountriesMultiMetric(int $limit = 10): array
    {
        $countries = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1')
            ->select([
                'countries.name',
                'countries.alpha_2_code',
                DB::raw('COUNT(venues.id) as venues'),
                DB::raw('SUM(venues.no_of_courts) as courts'),
                DB::raw('SUM(venues.no_of_glass_courts) as glass_courts'),
                DB::raw('SUM(venues.no_of_non_glass_courts) as non_glass_courts'),
                DB::raw('SUM(venues.no_of_outdoor_courts) as outdoor_courts'),
            ])
            ->groupBy('countries.id', 'countries.name', 'countries.alpha_2_code')
            ->orderBy('venues', 'desc')
            ->limit($limit)
            ->get();

        return [
            'limit' => $limit,
            'data' => $countries->toArray(),
        ];
    }

    /**
     * Get website statistics for venues.
     *
     * @param string|null $filter Geographic filter
     * @return array
     */
    public function websiteStats(?string $filter = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1');

        // Apply geographic filter
        $query = $this->applyGeographicFilter($query, $filter);

        $stats = $query->selectRaw('
                SUM(CASE WHEN venues.website IS NOT NULL AND venues.website != "" THEN 1 ELSE 0 END) as with_website,
                SUM(CASE WHEN venues.website IS NULL OR venues.website = "" THEN 1 ELSE 0 END) as without_website
            ')
            ->first();

        return [
            'labels' => ['Yes', 'No'],
            'data' => [
                (int) $stats->with_website,
                (int) $stats->without_website
            ],
        ];
    }

    /**
     * Get venues with elevation data for highest venues map.
     *
     * @return array
     */
    public function venuesWithElevation(): array
    {
        $venues = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1')
            ->whereNotNull('venues.latitude')
            ->whereNotNull('venues.longitude')
            ->whereNotNull('venues.elevation')
            ->where('venues.latitude', '!=', 0)
            ->where('venues.longitude', '!=', 0)
            ->where('venues.elevation', '!=', 0)
            ->select([
                'venues.id',
                'venues.name',
                'venues.physical_address',
                'venues.suburb',
                'venues.state',
                'venues.latitude',
                'venues.longitude',
                'venues.elevation',
                'venues.no_of_courts',
                'countries.name as country_name',
                'countries.alpha_2_code as country_code',
            ])
            ->orderBy('venues.elevation', 'desc')
            ->get();

        return $venues->map(function ($venue) {
            return [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->physical_address,
                'suburb' => $venue->suburb,
                'state' => $venue->state,
                'country' => $venue->country_name,
                'country_code' => $venue->country_code,
                'latitude' => (float) $venue->latitude,
                'longitude' => (float) $venue->longitude,
                'elevation' => (int) $venue->elevation,
                'courts' => $venue->no_of_courts ?? 'Unknown',
            ];
        })->toArray();
    }

    /**
     * Get venues at extreme latitudes (most northerly and southerly).
     *
     * @return array
     */
    public function extremeLatitudeVenues(): array
    {
        // Get top 20 most northerly venues (highest latitude)
        $northerly = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1')
            ->whereNotNull('venues.latitude')
            ->whereNotNull('venues.longitude')
            ->where('venues.latitude', '!=', 0)
            ->where('venues.longitude', '!=', 0)
            ->select([
                'venues.id',
                'venues.name',
                'venues.physical_address',
                'venues.suburb',
                'venues.state',
                'venues.latitude',
                'venues.longitude',
                'venues.no_of_courts',
                'countries.name as country_name',
                'countries.alpha_2_code as country_code',
            ])
            ->orderBy('venues.latitude', 'desc')
            ->limit(20)
            ->get();

        // Get top 20 most southerly venues (lowest latitude)
        $southerly = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1')
            ->whereNotNull('venues.latitude')
            ->whereNotNull('venues.longitude')
            ->where('venues.latitude', '!=', 0)
            ->where('venues.longitude', '!=', 0)
            ->select([
                'venues.id',
                'venues.name',
                'venues.physical_address',
                'venues.suburb',
                'venues.state',
                'venues.latitude',
                'venues.longitude',
                'venues.no_of_courts',
                'countries.name as country_name',
                'countries.alpha_2_code as country_code',
            ])
            ->orderBy('venues.latitude', 'asc')
            ->limit(20)
            ->get();

        return [
            'northerly' => $northerly->map(function ($venue) {
                return [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'address' => $venue->physical_address,
                    'suburb' => $venue->suburb,
                    'state' => $venue->state,
                    'country' => $venue->country_name,
                    'country_code' => $venue->country_code,
                    'latitude' => (float) $venue->latitude,
                    'longitude' => (float) $venue->longitude,
                    'courts' => $venue->no_of_courts ?? 'Unknown',
                ];
            })->toArray(),
            'southerly' => $southerly->map(function ($venue) {
                return [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'address' => $venue->physical_address,
                    'suburb' => $venue->suburb,
                    'state' => $venue->state,
                    'country' => $venue->country_name,
                    'country_code' => $venue->country_code,
                    'latitude' => (float) $venue->latitude,
                    'longitude' => (float) $venue->longitude,
                    'courts' => $venue->no_of_courts ?? 'Unknown',
                ];
            })->toArray(),
        ];
    }

    /**
     * Get hotels and resorts with squash courts.
     *
     * @return array
     */
    public function hotelsAndResorts(): array
    {
        $venues = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->join('venue_categories', 'venues.category_id', '=', 'venue_categories.id')
            ->join('regions', 'countries.region_id', '=', 'regions.id')
            ->join('continents', 'regions.continent_id', '=', 'continents.id')
            ->where('venues.status', '1')
            ->where(function($query) {
                $query->where('venue_categories.name', 'LIKE', '%Hotel%')
                      ->orWhere('venue_categories.name', 'LIKE', '%Resort%');
            })
            ->whereNotNull('venues.latitude')
            ->whereNotNull('venues.longitude')
            ->where('venues.latitude', '!=', 0)
            ->where('venues.longitude', '!=', 0)
            ->select([
                'venues.id',
                'venues.name',
                'venues.physical_address',
                'venues.suburb',
                'venues.state',
                'venues.latitude',
                'venues.longitude',
                'venues.no_of_courts',
                'countries.name as country_name',
                'countries.alpha_2_code as country_code',
                'venue_categories.name as category_name',
                'continents.id as continent_id',
                'continents.name as continent_name',
            ])
            ->orderBy('venues.name')
            ->get();

        return $venues->map(function ($venue) {
            return [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->physical_address,
                'suburb' => $venue->suburb,
                'state' => $venue->state,
                'country' => $venue->country_name,
                'country_code' => $venue->country_code,
                'category' => $venue->category_name,
                'continent_id' => $venue->continent_id,
                'continent_name' => $venue->continent_name,
                'latitude' => (float) $venue->latitude,
                'longitude' => (float) $venue->longitude,
                'courts' => $venue->no_of_courts ?? 'Unknown',
            ];
        })->toArray();
    }

    /**
     * Get countries with venues including population and land area statistics.
     *
     * @return array
     */
    public function countriesWithVenuesStats(): array
    {
        $countries = DB::connection('squash_remote')
            ->table('countries')
            ->leftJoin('venues', function($join) {
                $join->on('countries.id', '=', 'venues.country_id')
                     ->where('venues.status', '=', '1');
            })
            ->select([
                'countries.id',
                'countries.name',
                'countries.population',
                'countries.landarea',
                DB::raw('COUNT(DISTINCT venues.id) as venue_count'),
                DB::raw('SUM(CASE WHEN venues.no_of_courts IS NOT NULL THEN venues.no_of_courts ELSE 0 END) as total_courts')
            ])
            ->groupBy('countries.id', 'countries.name', 'countries.population', 'countries.landarea')
            ->havingRaw('COUNT(DISTINCT venues.id) > 0')
            ->orderBy('countries.name')
            ->get();

        return $countries->map(function ($country) {
            $population = (float) $country->population;
            $area = (float) $country->landarea;
            $venues = (int) $country->venue_count;
            $courts = (int) $country->total_courts;

            return [
                'id' => $country->id,
                'name' => $country->name,
                'population' => $population,
                'area_sq_km' => $area,
                'venues' => $venues,
                'courts' => $courts,
                // Calculated ratios
                'venues_per_population' => $population > 0 ? ($venues / $population) * 1000000 : 0, // per million
                'courts_per_population' => $population > 0 ? ($courts / $population) * 1000000 : 0, // per million
                'venues_per_area' => $area > 0 ? ($venues / $area) * 1000 : 0, // per 1000 sq km
                'courts_per_area' => $area > 0 ? ($courts / $area) * 1000 : 0, // per 1000 sq km
            ];
        })->toArray();
    }

    /**
     * Get venues with unknown number of courts.
     *
     * @return array
     */
    public function venuesWithUnknownCourts(): array
    {
        $venues = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->join('regions', 'countries.region_id', '=', 'regions.id')
            ->join('continents', 'regions.continent_id', '=', 'continents.id')
            ->leftJoin('venue_categories', 'venues.category_id', '=', 'venue_categories.id')
            ->where('venues.status', '1')
            ->where(function($query) {
                $query->whereNull('venues.no_of_courts')
                      ->orWhere('venues.no_of_courts', '=', 0);
            })
            ->whereNotNull('venues.latitude')
            ->whereNotNull('venues.longitude')
            ->where('venues.latitude', '!=', 0)
            ->where('venues.longitude', '!=', 0)
            ->select([
                'venues.id',
                'venues.name',
                'venues.physical_address',
                'venues.suburb',
                'venues.state',
                'venues.latitude',
                'venues.longitude',
                'countries.name as country_name',
                'countries.alpha_2_code as country_code',
                'venue_categories.name as category_name',
                'continents.id as continent_id',
                'continents.name as continent_name',
            ])
            ->orderBy('venues.name')
            ->get();

        return $venues->map(function ($venue) {
            return [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->physical_address,
                'suburb' => $venue->suburb,
                'state' => $venue->state,
                'country' => $venue->country_name,
                'country_code' => $venue->country_code,
                'category' => $venue->category_name ?? 'Unknown',
                'continent_id' => $venue->continent_id,
                'continent_name' => $venue->continent_name,
                'latitude' => (float) $venue->latitude,
                'longitude' => (float) $venue->longitude,
            ];
        })->toArray();
    }

    /**
     * Get the 100% Country Club data - countries with complete court count information.
     *
     * @return array
     */
    public function countryClub100Percent(): array
    {
        $countries = DB::connection('squash_remote')
            ->table('countries')
            ->leftJoin('venues', function($join) {
                $join->on('countries.id', '=', 'venues.country_id')
                     ->where('venues.status', '=', '1');
            })
            ->select([
                'countries.id',
                'countries.name',
                DB::raw('COUNT(DISTINCT venues.id) as total_venues'),
                DB::raw('COUNT(DISTINCT CASE WHEN venues.no_of_courts IS NOT NULL AND venues.no_of_courts > 0 THEN venues.id END) as venues_with_courts'),
                DB::raw('SUM(CASE WHEN venues.no_of_courts IS NOT NULL THEN venues.no_of_courts ELSE 0 END) as total_courts')
            ])
            ->groupBy('countries.id', 'countries.name')
            ->havingRaw('COUNT(DISTINCT venues.id) > 0')
            ->orderByRaw('(COUNT(DISTINCT CASE WHEN venues.no_of_courts IS NOT NULL AND venues.no_of_courts > 0 THEN venues.id END) / COUNT(DISTINCT venues.id) * 100) DESC')
            ->orderBy('countries.name')
            ->get();

        return $countries->map(function ($country) {
            $totalVenues = (int) $country->total_venues;
            $venuesWithCourts = (int) $country->venues_with_courts;
            $totalCourts = (int) $country->total_courts;
            
            $percentage = $totalVenues > 0 ? ($venuesWithCourts / $totalVenues) * 100 : 0;
            $courtsPerVenue = $venuesWithCourts > 0 ? $totalCourts / $venuesWithCourts : 0;

            return [
                'id' => $country->id,
                'name' => $country->name,
                'total_venues' => $totalVenues,
                'venues_with_courts' => $venuesWithCourts,
                'total_courts' => $totalCourts,
                'percentage' => round($percentage, 1),
                'courts_per_venue' => round($courtsPerVenue, 2),
            ];
        })->toArray();
    }

    /**
     * Get countries by number of venues for word cloud.
     *
     * @return array
     */
    public function countriesByVenuesWordCloud(): array
    {
        $countries = DB::connection('squash_remote')
            ->table('countries')
            ->join('venues', function($join) {
                $join->on('countries.id', '=', 'venues.country_id')
                     ->where('venues.status', '=', '1');
            })
            ->select([
                'countries.name',
                DB::raw('CAST(COUNT(DISTINCT venues.id) AS UNSIGNED) as venue_count')
            ])
            ->groupBy('countries.id', 'countries.name')
            ->havingRaw('COUNT(DISTINCT venues.id) > 0')
            ->orderBy('venue_count', 'desc')
            ->get();

        return $countries->map(function ($country) {
            return [
                'key' => $country->name,
                'value' => intval($country->venue_count), // Use intval() for more reliable conversion
            ];
        })->toArray();
    }

    /**
     * Get countries without any squash venues.
     *
     * @return array
     */
    public function countriesWithoutVenues(): array
    {
        // Get all countries
        $allCountries = DB::connection('squash_remote')
            ->table('countries')
            ->select('id', 'name', 'alpha_2_code', 'alpha_3_code')
            ->orderBy('name')
            ->get();

        // Get countries that have at least one active venue
        $countriesWithVenues = DB::connection('squash_remote')
            ->table('venues')
            ->where('status', '1')
            ->distinct()
            ->pluck('country_id')
            ->toArray();

        // Filter to get countries without venues
        $countriesWithoutVenues = $allCountries->filter(function ($country) use ($countriesWithVenues) {
            return !in_array($country->id, $countriesWithVenues);
        });

        return $countriesWithoutVenues->values()->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'alpha_2_code' => $country->alpha_2_code,
                'alpha_3_code' => $country->alpha_3_code,
            ];
        })->toArray();
    }

    /**
     * Get loneliest squash courts (venues with largest distance to nearest neighbor).
     *
     * @param int $limit
     * @return array
     */
    public function loneliestCourts(int $limit = 50): array
    {
        // Get the loneliest venue from each country (venue with max distance to nearest neighbor per country)
        // This ensures we have representation from all countries, not just the globally most isolated venues
        $venues = DB::connection('squash_remote')
            ->table('venues as v1')
            ->join('countries as c1', 'v1.country_id', '=', 'c1.id')
            ->join('venues as v2', 'v1.nearest_venue_id', '=', 'v2.id')
            ->join('countries as c2', 'v2.country_id', '=', 'c2.id')
            ->join(
                DB::connection('squash_remote')->raw('(
                    SELECT country_id, MAX(nearest_venue_km) as max_distance
                    FROM venues
                    WHERE status = "1"
                    AND nearest_venue_id IS NOT NULL
                    AND nearest_venue_km IS NOT NULL
                    AND latitude IS NOT NULL
                    AND longitude IS NOT NULL
                    AND latitude != 0
                    AND longitude != 0
                    GROUP BY country_id
                ) as max_per_country'),
                function ($join) {
                    $join->on('v1.country_id', '=', DB::raw('max_per_country.country_id'))
                        ->on('v1.nearest_venue_km', '=', DB::raw('max_per_country.max_distance'));
                }
            )
            ->where('v1.status', '1')
            ->where('v2.status', '1')
            ->whereNotNull('v1.nearest_venue_id')
            ->whereNotNull('v1.nearest_venue_km')
            ->whereNotNull('v1.latitude')
            ->whereNotNull('v1.longitude')
            ->whereNotNull('v2.latitude')
            ->whereNotNull('v2.longitude')
            ->where('v1.latitude', '!=', 0)
            ->where('v1.longitude', '!=', 0)
            ->where('v2.latitude', '!=', 0)
            ->where('v2.longitude', '!=', 0)
            ->select([
                'v1.id as venue_id',
                'v1.name as venue_name',
                'v1.physical_address as venue_address',
                'v1.suburb as venue_suburb',
                'v1.state as venue_state',
                'v1.latitude as venue_lat',
                'v1.longitude as venue_lng',
                'v1.no_of_courts as venue_courts',
                'c1.name as venue_country',
                'c1.alpha_2_code as venue_country_code',
                'v2.id as nearest_id',
                'v2.name as nearest_name',
                'v2.physical_address as nearest_address',
                'v2.suburb as nearest_suburb',
                'v2.state as nearest_state',
                'v2.latitude as nearest_lat',
                'v2.longitude as nearest_lng',
                'v2.no_of_courts as nearest_courts',
                'c2.name as nearest_country',
                'c2.alpha_2_code as nearest_country_code',
                'v1.nearest_venue_km as distance_km',
            ])
            ->orderBy('v1.nearest_venue_km', 'desc')
            ->limit($limit)
            ->get();

        return $venues->map(function ($venue) {
            return [
                'venue' => [
                    'id' => $venue->venue_id,
                    'name' => $venue->venue_name,
                    'address' => $venue->venue_address,
                    'suburb' => $venue->venue_suburb,
                    'state' => $venue->venue_state,
                    'country' => $venue->venue_country,
                    'country_code' => $venue->venue_country_code,
                    'latitude' => (float) $venue->venue_lat,
                    'longitude' => (float) $venue->venue_lng,
                    'courts' => $venue->venue_courts ?? 'Unknown',
                ],
                'nearest' => [
                    'id' => $venue->nearest_id,
                    'name' => $venue->nearest_name,
                    'address' => $venue->nearest_address,
                    'suburb' => $venue->nearest_suburb,
                    'state' => $venue->nearest_state,
                    'country' => $venue->nearest_country,
                    'country_code' => $venue->nearest_country_code,
                    'latitude' => (float) $venue->nearest_lat,
                    'longitude' => (float) $venue->nearest_lng,
                    'courts' => $venue->nearest_courts ?? 'Unknown',
                ],
                'distance_km' => (float) $venue->distance_km,
            ];
        })->toArray();
    }

    /**
     * Get squash court graveyard (deleted/closed venues).
     *
     * @param array $filters Optional filters: country, delete_reason_id
     * @return array
     */
    public function courtGraveyard(array $filters = []): array
    {
        $query = DB::connection('squash_remote')
            ->table('venues as v')
            ->leftJoin('venue_delete_reasons as vdr', 'v.delete_reason_id', '=', 'vdr.id')
            ->join('countries as c', 'v.country_id', '=', 'c.id')
            ->whereIn('v.status', ['3', '4']) // FlaggedForDeletion or Deleted
            ->whereNotNull('v.date_deleted');

        // Apply filters
        if (!empty($filters['country'])) {
            $query->where('c.alpha_2_code', $filters['country']);
        }

        if (!empty($filters['delete_reason_id'])) {
            $query->where('v.delete_reason_id', $filters['delete_reason_id']);
        }

        $venues = $query->select([
                'v.id',
                'v.name',
                'v.physical_address',
                'v.suburb',
                'v.state',
                'v.no_of_courts',
                'v.status',
                'v.reason_for_deletion',
                'v.date_deleted',
                'c.name as country_name',
                'c.alpha_2_code as country_code',
                'vdr.id as delete_reason_id',
                'vdr.name as delete_reason',
            ])
            ->orderBy('v.date_deleted', 'desc')
            ->get();

        return $venues->map(function ($venue) {
            return [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->physical_address,
                'suburb' => $venue->suburb,
                'state' => $venue->state,
                'country' => $venue->country_name,
                'country_code' => $venue->country_code,
                'courts' => $venue->no_of_courts ?? null,
                'delete_reason_id' => $venue->delete_reason_id,
                'delete_reason' => $venue->delete_reason ?? 'Other',
                'reason_details' => $venue->reason_for_deletion,
                'date_deleted' => $venue->date_deleted,
            ];
        })->toArray();
    }

    /**
     * Get list of venue deletion reasons.
     *
     * @return array
     */
    public function deletionReasons(): array
    {
        $reasons = DB::connection('squash_remote')
            ->table('venue_delete_reasons')
            ->orderBy('sort_by')
            ->get(['id', 'name']);

        return $reasons->toArray();
    }
}

