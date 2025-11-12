<?php

use App\Http\Controllers\Api\SquashStatsController;
use App\Http\Controllers\Api\GeographicSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rate limiting applied to prevent scraping:
| - Local development: 1000 requests per minute (effectively unlimited)
| - Production: Should use 60 req/min for stats, 20 req/min for map
|
*/

// Determine rate limit based on environment
// Local: Very high limits for gallery page testing (14 iframes loading simultaneously)
// Production: 300 req/min for stats (5 per second), 120 req/min for map (2 per second)
$rateLimit = app()->environment('local') ? '10000,1' : '300,1';
$mapRateLimit = app()->environment('local') ? '10000,1' : '120,1';

// Aggregate statistics endpoints
Route::prefix('squash')->middleware("throttle:{$rateLimit}")->group(function () {
    Route::get('/country-stats', [SquashStatsController::class, 'countryStats']);
    Route::get('/top-countries', [SquashStatsController::class, 'topCountries']);
    Route::get('/top-countries-multi', [SquashStatsController::class, 'topCountriesMultiMetric']);
    Route::get('/court-distribution', [SquashStatsController::class, 'courtDistribution']);
    Route::get('/court-types', [SquashStatsController::class, 'courtTypes']);
    Route::get('/timeline', [SquashStatsController::class, 'timeline']);
    Route::get('/venue-types', [SquashStatsController::class, 'venueTypes']);
    Route::get('/regional-breakdown', [SquashStatsController::class, 'regionalBreakdown']);
    Route::get('/subcontinental-breakdown', [SquashStatsController::class, 'subContinentalBreakdown']);
    Route::get('/venues-by-state', [SquashStatsController::class, 'venuesByState']);
    Route::get('/website-stats', [SquashStatsController::class, 'websiteStats']);
    Route::get('/top-venues-by-courts', [SquashStatsController::class, 'topVenuesByCourts']);
    
    // Geographic search endpoints
    Route::get('/search-areas', [GeographicSearchController::class, 'search']);
    Route::get('/filter-details', [GeographicSearchController::class, 'getFilterDetails']);
});

// Map data endpoint
Route::prefix('squash')->middleware("throttle:{$mapRateLimit}")->group(function () {
    Route::get('/map', [SquashStatsController::class, 'mapData']);
});

// Chart and Dashboard metadata endpoints (for WordPress plugin admin)
Route::get('/charts', function () {
    return response()->json(\App\Services\ChartRegistry::all());
});

Route::get('/dashboards', function () {
    return response()->json(\App\Services\DashboardRegistry::all());
});

Route::get('/categories', [SquashStatsController::class, 'categories']);

