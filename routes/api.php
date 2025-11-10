<?php

use App\Http\Controllers\Api\SquashStatsController;
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
$rateLimit = app()->environment('local') ? '1000,1' : '60,1';
$mapRateLimit = app()->environment('local') ? '1000,1' : '20,1';

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
});

// Map data endpoint
Route::prefix('squash')->middleware("throttle:{$mapRateLimit}")->group(function () {
    Route::get('/map', [SquashStatsController::class, 'mapData']);
});

