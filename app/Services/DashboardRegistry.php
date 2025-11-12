<?php

namespace App\Services;

class DashboardRegistry
{
    /**
     * Get all available dashboards with their metadata.
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            'world' => [
                'id' => 'world',
                'name' => 'World Statistics',
                'description' => 'Complete global overview with all charts and interactive map',
                'route' => 'dashboard.world',
                'url' => '/',
                'charts' => [
                    'summary-stats',
                    'venue-map',
                    'continental-breakdown',
                    'subcontinental-breakdown',
                    'timeline',
                    'top-venues',
                    'court-distribution',
                    'top-courts',
                    'venue-categories',
                    'website-stats',
                    'outdoor-courts',
                ],
                'thumbnail' => '/images/dashboards/world.png',
            ],
            'country' => [
                'id' => 'country',
                'name' => 'Country Statistics',
                'description' => 'Detailed breakdown by country with filtered data',
                'route' => 'dashboard.country',
                'url' => '/country',
                'charts' => [
                    'summary-stats',
                    'venue-map',
                    'top-venues',
                    'court-distribution',
                    'venue-categories',
                    'website-stats',
                    'timeline',
                ],
                'thumbnail' => '/images/dashboards/country.png',
            ],
            'venue-types' => [
                'id' => 'venue-types',
                'name' => 'Venue Types & Categories',
                'description' => 'Focus on venue characteristics and categories',
                'route' => 'dashboard.venue-types',
                'url' => '/venue-types',
                'charts' => [
                    'summary-stats',
                    'venue-categories',
                    'court-distribution',
                    'website-stats',
                    'outdoor-courts',
                    'continental-breakdown',
                    'subcontinental-breakdown',
                ],
                'thumbnail' => '/images/dashboards/venue-types.png',
            ],
        ];
    }

    /**
     * Get a single dashboard by ID.
     *
     * @param string $id
     * @return array|null
     */
    public static function get(string $id): ?array
    {
        $dashboards = self::all();
        return $dashboards[$id] ?? null;
    }
}

