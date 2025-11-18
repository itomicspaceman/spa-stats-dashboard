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
    public function countryStats(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        $cacheKey = $filter ? "squash:country_stats:{$filter}" : 'squash:country_stats';

        $data = Cache::remember($cacheKey, 10800, function () use ($filter) {
            return $this->aggregator->countryStats($filter);
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
            'filter' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $metric = $request->input('metric', 'venues');
        $limit = $request->input('limit', 30);
        $filter = $request->input('filter');

        $cacheKey = $filter 
            ? "squash:top_countries:{$metric}:{$limit}:{$filter}"
            : "squash:top_countries:{$metric}:{$limit}";

        $data = Cache::remember($cacheKey, 10800, function () use ($metric, $limit, $filter) {
            return $this->aggregator->topCountriesBy($metric, $limit, $filter);
        });

        return response()->json($data);
    }

    /**
     * Get court distribution data.
     */
    public function courtDistribution(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        $cacheKey = $filter ? "squash:court_distribution:{$filter}" : 'squash:court_distribution';

        $data = Cache::remember($cacheKey, 10800, function () use ($filter) {
            return $this->aggregator->courtDistribution($filter);
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
            'filter' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $interval = $request->input('interval', 'monthly');
        $filter = $request->input('filter');
        $cacheKey = $filter 
            ? "squash:timeline:{$interval}:{$filter}"
            : "squash:timeline:{$interval}";

        $data = Cache::remember($cacheKey, 10800, function () use ($interval, $filter) {
            return $this->aggregator->timeline($interval, $filter);
        });

        return response()->json($data);
    }

    /**
     * Get venue types breakdown.
     */
    public function venueTypes(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        $cacheKey = $filter ? "squash:venue_types:{$filter}" : 'squash:venue_types';

        $data = Cache::remember($cacheKey, 10800, function () use ($filter) {
            return $this->aggregator->venueTypes($filter);
        });

        return response()->json($data);
    }

    /**
     * Get map data as GeoJSON.
     */
    public function mapData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filter' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $filter = $request->input('filter');
        $cacheKey = $filter ? "squash:map_data:{$filter}" : 'squash:map_data';

        $data = Cache::remember($cacheKey, 10800, function () use ($filter) {
            return $this->aggregator->mapPoints($filter);
        });

        return response()->json($data);
    }

    /**
     * Get regional breakdown of venues and courts.
     */
    public function regionalBreakdown(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        $cacheKey = $filter ? "squash:regional_breakdown:{$filter}" : 'squash:regional_breakdown';

        $data = Cache::remember($cacheKey, 10800, function () use ($filter) {
            return $this->aggregator->regionalBreakdown($filter);
        });

        return response()->json($data);
    }

    /**
     * Get sub-continental breakdown of venues and courts.
     */
    public function subContinentalBreakdown(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        $cacheKey = $filter ? "squash:subcontinental_breakdown:{$filter}" : 'squash:subcontinental_breakdown';

        $data = Cache::remember($cacheKey, 10800, function () use ($filter) {
            return $this->aggregator->subContinentalBreakdown($filter);
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
        $filter = $request->input('filter');
        $cacheKey = $filter ? "squash:venues_by_state:{$filter}" : 'squash:venues_by_state';

        $data = Cache::remember($cacheKey, 10800, function () use ($filter) {
            return $this->aggregator->stateBreakdown($filter);
        });

        return response()->json($data);
    }

    /**
     * Get top venues by number of courts.
     */
    public function topVenuesByCourts(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        $limit = $request->input('limit', 20);
        $cacheKey = $filter ? "squash:top_venues_by_courts:{$limit}:{$filter}" : "squash:top_venues_by_courts:{$limit}";

        $data = Cache::remember($cacheKey, 10800, function () use ($limit, $filter) {
            return $this->aggregator->topVenuesByCourts($limit, $filter);
        });

        return response()->json($data);
    }

    /**
     * Get website statistics for venues.
     */
    public function websiteStats(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        $cacheKey = $filter ? "squash:website_stats:{$filter}" : 'squash:website_stats';

        $data = Cache::remember($cacheKey, 10800, function () use ($filter) {
            return $this->aggregator->websiteStats($filter);
        });

        return response()->json($data);
    }

    /**
     * Get countries without any squash venues.
     */
    public function countriesWithoutVenues(): JsonResponse
    {
        $cacheKey = 'squash:countries_without_venues';

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->countriesWithoutVenues();
        });

        return response()->json($data);
    }

    /**
     * Get venues with elevation data.
     */
    public function venuesWithElevation(): JsonResponse
    {
        $cacheKey = 'squash:venues_with_elevation';

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->venuesWithElevation();
        });

        return response()->json($data);
    }

    /**
     * Get venues at extreme latitudes (most northerly and southerly).
     */
    public function extremeLatitudeVenues(): JsonResponse
    {
        $cacheKey = 'squash:extreme_latitude_venues';

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->extremeLatitudeVenues();
        });

        return response()->json($data);
    }

    /**
     * Get hotels and resorts with squash courts.
     */
    public function hotelsAndResorts(): JsonResponse
    {
        $cacheKey = 'squash:hotels_and_resorts';

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->hotelsAndResorts();
        });

        return response()->json($data);
    }

    /**
     * Get countries with venues including population and area statistics.
     */
    public function countriesWithVenuesStats(): JsonResponse
    {
        $cacheKey = 'squash:countries_with_venues_stats';

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->countriesWithVenuesStats();
        });

        return response()->json($data);
    }

    /**
     * Get venues with unknown number of courts.
     */
    public function venuesWithUnknownCourts(): JsonResponse
    {
        $cacheKey = 'squash:venues_with_unknown_courts';

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->venuesWithUnknownCourts();
        });

        return response()->json($data);
    }

    /**
     * Get the 100% Country Club data.
     */
    public function countryClub100Percent(): JsonResponse
    {
        $cacheKey = 'squash:country_club_100_percent';

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->countryClub100Percent();
        });

        return response()->json($data);
    }

    /**
     * Get countries by venues for word cloud.
     */
    public function countriesByVenuesWordCloud(): JsonResponse
    {
        $cacheKey = 'squash:countries_wordcloud';

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->countriesByVenuesWordCloud();
        });

        return response()->json($data);
    }

    /**
     * Get loneliest squash courts.
     */
    public function loneliestCourts(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        
        // Determine limit based on filter type
        $limit = 250; // Default for world/continent/region (one per country)
        
        if ($filter) {
            $parts = explode(':', $filter, 2);
            if (count($parts) === 2) {
                [$type, $code] = $parts;
                // For country view, show top 20; for state view, show top 10
                if ($type === 'country') {
                    $limit = 20;
                } elseif ($type === 'state') {
                    $limit = 10;
                }
            }
        }

        $cacheKey = $filter 
            ? "squash:loneliest_courts:{$filter}:{$limit}" 
            : "squash:loneliest_courts:world:{$limit}";

        $data = Cache::remember($cacheKey, 10800, function () use ($filter, $limit) {
            return $this->aggregator->loneliestCourts($filter, $limit);
        });

        return response()->json($data);
    }

    /**
     * Get squash court graveyard (deleted/closed venues).
     */
    public function courtGraveyard(Request $request): JsonResponse
    {
        $filters = [];
        
        if ($request->has('country')) {
            $filters['country'] = $request->input('country');
        }
        
        if ($request->has('delete_reason_id')) {
            $filters['delete_reason_id'] = $request->input('delete_reason_id');
        }

        $cacheKey = "squash:court_graveyard:" . md5(json_encode($filters));

        $data = Cache::remember($cacheKey, 10800, function () use ($filters) {
            return $this->aggregator->courtGraveyard($filters);
        });

        return response()->json($data);
    }

    /**
     * Get nearest courts (venues within 0.3km of each other).
     */
    public function nearestCourts(Request $request): JsonResponse
    {
        $cacheKey = "squash:nearest_courts";

        $data = Cache::remember($cacheKey, 10800, function () {
            return $this->aggregator->nearestCourts();
        });

        return response()->json($data);
    }

    /**
     * Get list of venue deletion reasons.
     */
    public function deletionReasons(): JsonResponse
    {
        $cacheKey = "squash:deletion_reasons";

        $data = Cache::remember($cacheKey, 86400, function () {
            return $this->aggregator->deletionReasons();
        });

        return response()->json($data);
    }
}
