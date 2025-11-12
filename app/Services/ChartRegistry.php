<?php

namespace App\Services;

class ChartRegistry
{
    /**
     * Get all available charts with their metadata.
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            'summary-stats' => [
                'id' => 'summary-stats',
                'name' => 'Summary Statistics',
                'description' => 'Key metrics: total countries, venues with squash, total venues, and total courts',
                'component' => 'charts.summary-stats',
                'api_endpoints' => ['/country-stats'],
                'category' => 'overview',
                'thumbnail' => '/images/charts/summary-stats.png',
                'relevant_levels' => ['world', 'continent', 'region', 'country', 'state'], // Always relevant
            ],
            'venue-map' => [
                'id' => 'venue-map',
                'name' => 'Venue Map',
                'description' => 'Interactive map showing squash venues with clustering',
                'component' => 'charts.venue-map',
                'api_endpoints' => ['/map'],
                'category' => 'map',
                'thumbnail' => '/images/charts/venue-map.png',
                'relevant_levels' => ['world', 'continent', 'region', 'country', 'state'], // Always relevant
            ],
            'continental-breakdown' => [
                'id' => 'continental-breakdown',
                'name' => 'Squash Venues & Courts by Continent',
                'description' => 'Bar chart comparing squash venues and courts across continents',
                'component' => 'charts.continental-breakdown',
                'api_endpoints' => ['/regional-breakdown'],
                'category' => 'regional',
                'thumbnail' => '/images/charts/continental-breakdown.png',
                'relevant_levels' => ['world'], // Only at world level
            ],
            'subcontinental-breakdown' => [
                'id' => 'subcontinental-breakdown',
                'name' => 'Squash Venues & Courts by Region',
                'description' => 'Detailed regional breakdown of squash venues by region',
                'component' => 'charts.subcontinental-breakdown',
                'api_endpoints' => ['/subcontinental-breakdown'],
                'category' => 'regional',
                'thumbnail' => '/images/charts/subcontinental-breakdown.png',
                'relevant_levels' => ['world', 'continent'], // World or continent level
            ],
            'timeline' => [
                'id' => 'timeline',
                'name' => 'Squash Venues Added Over Time',
                'description' => 'Line chart showing squash venue growth over time',
                'component' => 'charts.timeline',
                'api_endpoints' => ['/timeline'],
                'category' => 'trends',
                'thumbnail' => '/images/charts/timeline.png',
                'relevant_levels' => ['world', 'continent', 'region', 'country', 'state'], // Always relevant
            ],
            'state-breakdown' => [
                'id' => 'state-breakdown',
                'name' => 'Squash Venues & Courts by State/County',
                'description' => 'Bar chart showing squash venues and courts by state/county within a country',
                'component' => 'charts.state-breakdown',
                'api_endpoints' => ['/venues-by-state'],
                'category' => 'regional',
                'thumbnail' => '/images/charts/state-breakdown.png',
                'relevant_levels' => ['country'], // Only at country level
            ],
            'venues-by-state-pie' => [
                'id' => 'venues-by-state-pie',
                'name' => 'Squash Venues by State/County (Pie)',
                'description' => 'Pie chart showing distribution of squash venues by state/county within a country',
                'component' => 'charts.venues-by-state-pie',
                'api_endpoints' => ['/venues-by-state'],
                'category' => 'regional',
                'thumbnail' => '/images/charts/venues-by-state-pie.png',
                'relevant_levels' => ['country'], // Only at country level
            ],
            'top-venues-by-courts' => [
                'id' => 'top-venues-by-courts',
                'name' => 'Top 20 Squash Venues by Courts',
                'description' => 'Horizontal bar chart of squash venues with the most courts',
                'component' => 'charts.top-venues-by-courts',
                'api_endpoints' => ['/top-venues-by-courts?limit=20'],
                'category' => 'rankings',
                'thumbnail' => '/images/charts/top-venues-by-courts.png',
                'relevant_levels' => ['world', 'continent', 'region', 'country', 'state'], // Always relevant
            ],
            'top-venues' => [
                'id' => 'top-venues',
                'name' => 'Top 20 Countries by Squash Venues',
                'description' => 'Horizontal bar chart of countries with most squash venues',
                'component' => 'charts.top-venues',
                'api_endpoints' => ['/top-countries?metric=venues&limit=20'],
                'category' => 'rankings',
                'thumbnail' => '/images/charts/top-venues.png',
                'relevant_levels' => ['world', 'continent', 'region'], // Not for single country/state
            ],
            'court-distribution' => [
                'id' => 'court-distribution',
                'name' => 'Squash Courts Per Venue',
                'description' => 'Distribution showing how many squash courts venues typically have',
                'component' => 'charts.court-distribution',
                'api_endpoints' => ['/court-distribution'],
                'category' => 'analysis',
                'thumbnail' => '/images/charts/court-distribution.png',
                'relevant_levels' => ['world', 'continent', 'region', 'country', 'state'], // Always relevant
            ],
            'top-courts' => [
                'id' => 'top-courts',
                'name' => 'Top 20 Countries by Squash Courts',
                'description' => 'Horizontal bar chart of countries with most squash courts',
                'component' => 'charts.top-courts',
                'api_endpoints' => ['/top-countries?metric=courts&limit=20'],
                'category' => 'rankings',
                'thumbnail' => '/images/charts/top-courts.png',
                'relevant_levels' => ['world', 'continent', 'region'], // Not for single country/state
            ],
            'venue-categories' => [
                'id' => 'venue-categories',
                'name' => 'Squash Venues by Category',
                'description' => 'Doughnut chart showing squash venue type distribution',
                'component' => 'charts.venue-categories',
                'api_endpoints' => ['/categories'],
                'category' => 'analysis',
                'thumbnail' => '/images/charts/venue-categories.png',
                'relevant_levels' => ['world', 'continent', 'region', 'country', 'state'], // Always relevant
            ],
            'website-stats' => [
                'id' => 'website-stats',
                'name' => 'Squash Venues with Websites',
                'description' => 'Pie chart showing percentage of squash venues with website information',
                'component' => 'charts.website-stats',
                'api_endpoints' => ['/website-stats'],
                'category' => 'analysis',
                'thumbnail' => '/images/charts/website-stats.png',
                'relevant_levels' => ['world', 'continent', 'region', 'country', 'state'], // Always relevant
            ],
            'outdoor-courts' => [
                'id' => 'outdoor-courts',
                'name' => 'Top 20 Countries by Outdoor Squash Courts',
                'description' => 'Horizontal bar chart of countries with most outdoor squash courts',
                'component' => 'charts.outdoor-courts',
                'api_endpoints' => ['/top-countries?metric=outdoor_courts&limit=20'],
                'category' => 'rankings',
                'thumbnail' => '/images/charts/outdoor-courts.png',
                'relevant_levels' => ['world', 'continent', 'region'], // Not for single country/state
            ],
        ];
    }

    /**
     * Get a single chart by ID.
     *
     * @param string $id
     * @return array|null
     */
    public static function get(string $id): ?array
    {
        $charts = self::all();
        return $charts[$id] ?? null;
    }

    /**
     * Get charts by category.
     *
     * @param string $category
     * @return array
     */
    public static function byCategory(string $category): array
    {
        return array_filter(self::all(), function ($chart) use ($category) {
            return $chart['category'] === $category;
        });
    }

    /**
     * Get all available categories.
     *
     * @return array
     */
    public static function categories(): array
    {
        return [
            'overview' => 'Overview',
            'map' => 'Maps',
            'regional' => 'Regional Analysis',
            'trends' => 'Trends',
            'rankings' => 'Rankings',
            'analysis' => 'Analysis',
        ];
    }

    /**
     * Get charts relevant for a specific geographic level.
     *
     * @param string|null $filter Geographic filter (e.g., "continent:1", "country:US")
     * @return array
     */
    public static function getRelevantCharts(?string $filter): array
    {
        $level = self::determineFilterLevel($filter);
        $allCharts = self::all();
        
        return array_filter($allCharts, function ($chart) use ($level) {
            return in_array($level, $chart['relevant_levels'] ?? ['world']);
        });
    }

    /**
     * Determine the geographic level from a filter string.
     *
     * @param string|null $filter
     * @return string
     */
    protected static function determineFilterLevel(?string $filter): string
    {
        if (!$filter) {
            return 'world';
        }

        $parts = explode(':', $filter, 2);
        if (count($parts) !== 2) {
            return 'world';
        }

        [$type, $code] = $parts;

        return match ($type) {
            'continent' => 'continent',
            'region' => 'region',
            'country' => 'country',
            'state' => 'state',
            default => 'world',
        };
    }
}

