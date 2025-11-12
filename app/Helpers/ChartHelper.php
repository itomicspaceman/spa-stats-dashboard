<?php

namespace App\Helpers;

use App\Services\ChartRegistry;

class ChartHelper
{
    /**
     * Check if a chart is relevant for the current filter level.
     *
     * @param string $chartId
     * @param string|null $filter
     * @return bool
     */
    public static function isChartRelevant(string $chartId, ?string $filter): bool
    {
        $chart = ChartRegistry::get($chartId);
        
        if (!$chart) {
            return false;
        }
        
        $level = self::determineFilterLevel($filter);
        $relevantLevels = $chart['relevant_levels'] ?? ['world'];
        
        return in_array($level, $relevantLevels);
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

