<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Region;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

class SquashDataAggregator
{
    /**
     * Get comprehensive country-level statistics.
     *
     * @return array
     */
    public function countryStats(): array
    {
        $stats = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1') // Approved only
            ->select([
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

        return [
            'total_countries' => Country::count(),
            'countries_with_venues' => $stats->count(),
            'total_venues' => Venue::approved()->count(),
            'total_courts' => (int) Venue::approved()->sum('no_of_courts'),
        ];
    }

    /**
     * Get top countries by specified metric.
     *
     * @param string $metric
     * @param int $limit
     * @return array
     */
    public function topCountriesBy(string $metric = 'venues', int $limit = 30): array
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

        $results = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->where('venues.status', '1')
            ->select([
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
     * @return array
     */
    public function courtDistribution(): array
    {
        // Get all approved venues
        $allVenues = Venue::approved()
            ->select('no_of_courts', DB::raw('COUNT(*) as count'))
            ->groupBy('no_of_courts')
            ->orderBy('no_of_courts')
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
     * @return array
     */
    public function timeline(string $interval = 'monthly'): array
    {
        $dateFormat = match ($interval) {
            'yearly' => '%Y',
            'monthly' => '%Y-%m',
            'weekly' => '%Y-%u',
            default => '%Y-%m',
        };

        $timeline = Venue::approved()
            ->select(DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"), DB::raw('COUNT(*) as count'))
            ->whereNotNull('created_at')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $timeline->toArray();
    }

    /**
     * Get venue types breakdown (membership model, categories).
     *
     * @return array
     */
    public function venueTypes(): array
    {
        // Category breakdown
        $categories = DB::connection('squash_remote')
            ->table('venues')
            ->join('venue_categories', 'venues.category_id', '=', 'venue_categories.id')
            ->where('venues.status', '1')
            ->select('venue_categories.name', DB::raw('COUNT(*) as venue_count'))
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
     * @param array $filters
     * @return array
     */
    public function mapPoints(array $filters = []): array
    {
        $query = Venue::approved()->withCoordinates();

        // Apply optional filters
        if (isset($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (isset($filters['min_courts'])) {
            $query->where('no_of_courts', '>=', $filters['min_courts']);
        }

        $venues = $query->with('country')->get();

        $features = $venues->map(function ($venue) {
            // Build full address
            $addressParts = array_filter([
                $venue->address_line_1,
                $venue->address_line_2,
                $venue->suburb,
                $venue->state,
                $venue->postcode,
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
                    'country' => $venue->country->name ?? 'Unknown',
                    'country_code' => $venue->country->alpha_2_code ?? null,
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
     * @return array
     */
    public function regionalBreakdown(): array
    {
        $continents = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->join('regions', 'countries.region_id', '=', 'regions.id')
            ->join('continents', 'regions.continent_id', '=', 'continents.id')
            ->where('venues.status', '1')
            ->select([
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
     * @return array
     */
    public function subContinentalBreakdown(): array
    {
        $regions = DB::connection('squash_remote')
            ->table('venues')
            ->join('countries', 'venues.country_id', '=', 'countries.id')
            ->join('regions', 'countries.region_id', '=', 'regions.id')
            ->where('venues.status', '1')
            ->select([
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
     * @return array
     */
    public function websiteStats(): array
    {
        $stats = DB::connection('squash_remote')
            ->table('venues')
            ->where('status', '1')
            ->selectRaw('
                SUM(CASE WHEN website IS NOT NULL AND website != "" THEN 1 ELSE 0 END) as with_website,
                SUM(CASE WHEN website IS NULL OR website = "" THEN 1 ELSE 0 END) as without_website
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
}

