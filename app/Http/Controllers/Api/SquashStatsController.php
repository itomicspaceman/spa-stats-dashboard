<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SquashDataAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SquashStatsController extends Controller
{
    public function __construct(
        protected SquashDataAggregator $aggregator
    ) {}

    /**
     * Get comprehensive country statistics.
     */
    public function countryStats(): JsonResponse
    {
        $data = Cache::remember('squash:country_stats', 10800, function () {
            return $this->aggregator->countryStats();
        });

        return response()->json($data);
    }

    /**
     * Get top countries by specified metric.
     */
    public function topCountries(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metric' => 'string|in:venues,courts,glass_courts,outdoor_courts',
            'limit' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $metric = $request->input('metric', 'venues');
        $limit = $request->input('limit', 30);

        $cacheKey = "squash:top_countries:{$metric}:{$limit}";

        $data = Cache::remember($cacheKey, 10800, function () use ($metric, $limit) {
            return $this->aggregator->topCountriesBy($metric, $limit);
        });

        return response()->json($data);
    }

    /**
     * Get court distribution data.
     */
    public function courtDistribution(): JsonResponse
    {
        $data = Cache::remember('squash:court_distribution', 10800, function () {
            return $this->aggregator->courtDistribution();
        });

        return response()->json($data);
    }

    /**
     * Get timeline data.
     */
    public function timeline(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'interval' => 'string|in:daily,weekly,monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $interval = $request->input('interval', 'monthly');
        $cacheKey = "squash:timeline:{$interval}";

        $data = Cache::remember($cacheKey, 10800, function () use ($interval) {
            return $this->aggregator->timeline($interval);
        });

        return response()->json($data);
    }

    /**
     * Get venue types breakdown.
     */
    public function venueTypes(): JsonResponse
    {
        $data = Cache::remember('squash:venue_types', 10800, function () {
            return $this->aggregator->venueTypes();
        });

        return response()->json($data);
    }

    /**
     * Get map data as GeoJSON.
     */
    public function mapData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'integer|exists:squash_remote.countries,id',
            'min_courts' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $filters = $request->only(['country_id', 'min_courts']);

        // For now, we'll cache the full map without filters
        // In production, you might want to cache filtered versions too
        $data = Cache::remember('squash:map_data', 10800, function () {
            return $this->aggregator->mapPoints();
        });

        return response()->json($data);
    }

    /**
     * Get regional breakdown of venues and courts.
     */
    public function regionalBreakdown(): JsonResponse
    {
        $data = Cache::remember('squash:regional_breakdown', 10800, function () {
            return $this->aggregator->regionalBreakdown();
        });

        return response()->json($data);
    }

    /**
     * Get sub-continental breakdown of venues and courts.
     */
    public function subContinentalBreakdown(): JsonResponse
    {
        $data = Cache::remember('squash:subcontinental_breakdown', 10800, function () {
            return $this->aggregator->subContinentalBreakdown();
        });

        return response()->json($data);
    }

    /**
     * Get court types breakdown (glass, non-glass, indoor, outdoor).
     */
    public function courtTypes(): JsonResponse
    {
        $data = Cache::remember('squash:court_types', 10800, function () {
            return $this->aggregator->courtTypesBreakdown();
        });

        return response()->json($data);
    }

    /**
     * Get top countries with multiple metrics for comparison.
     */
    public function topCountriesMultiMetric(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $limit = $request->input('limit', 30);
        $cacheKey = "squash:top_countries_multi:{$limit}";

        $data = Cache::remember($cacheKey, 10800, function () use ($limit) {
            return $this->aggregator->topCountriesMultiMetric($limit);
        });

        return response()->json($data);
    }

    /**
     * Get venues by state/province for a specific country.
     */
    public function venuesByState(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|integer|exists:squash_remote.countries,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $countryId = $request->input('country_id');
        $cacheKey = "squash:venues_by_state:{$countryId}";

        $data = Cache::remember($cacheKey, 10800, function () use ($countryId) {
            return $this->aggregator->venuesByState($countryId);
        });

        return response()->json($data);
    }

    /**
     * Get website statistics for venues.
     */
    public function websiteStats(): JsonResponse
    {
        $data = Cache::remember('squash:website_stats', 10800, function () {
            return $this->aggregator->websiteStats();
        });

        return response()->json($data);
    }
}
