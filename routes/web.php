<?php

use App\Http\Controllers\ChartController;
use App\Http\Controllers\GeographicAreasController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

// Dashboard Routes
Route::get('/', function () {
    return view('dashboards.world');
})->name('dashboard.world');

Route::get('/country/{code?}', function ($code = null) {
    return view('dashboards.country', compact('code'));
})->name('dashboard.country');

Route::get('/venue-types', function () {
    return view('dashboards.venue-types');
})->name('dashboard.venue-types');

Route::get('/trivia', function () {
    return view('trivia.index');
})->name('trivia.index');

Route::get('/trivia/countries-without-venues', function () {
    return view('trivia.index', ['activeMap' => 'countries-without-venues']);
})->name('trivia.countries-without-venues');

Route::get('/trivia/high-altitude-venues', function () {
    return view('trivia.index', ['activeMap' => 'highest-venues']);
})->name('trivia.high-altitude-venues');

Route::get('/trivia/extreme-latitude-venues', function () {
    return view('trivia.index', ['activeMap' => 'extreme-latitude']);
})->name('trivia.extreme-latitude-venues');

Route::get('/trivia/hotels-resorts', function () {
    return view('trivia.index', ['activeMap' => 'hotels-resorts']);
})->name('trivia.hotels-resorts');

Route::get('/trivia/countries-stats', function () {
    return view('trivia.index', ['activeMap' => 'countries-stats']);
})->name('trivia.countries-stats');

Route::get('/trivia/unknown-courts', function () {
    return view('trivia.index', ['activeMap' => 'unknown-courts']);
})->name('trivia.unknown-courts');

Route::get('/trivia/country-club-100', function () {
    return view('trivia.index', ['activeMap' => 'country-club-100']);
})->name('trivia.country-club-100');

Route::get('/trivia/countries-wordcloud', function () {
    return view('trivia.index', ['activeMap' => 'countries-wordcloud']);
})->name('trivia.countries-wordcloud');

Route::get('/trivia/loneliest-courts', function () {
    return view('trivia.index', ['activeMap' => 'loneliest-courts']);
})->name('trivia.loneliest-courts');

Route::get('/trivia/court-graveyard', function () {
    return view('trivia.index', ['activeMap' => 'court-graveyard']);
})->name('trivia.court-graveyard');

// Dynamic chart rendering
Route::get('/render', [ChartController::class, 'render'])->name('charts.render');

// Chart gallery
Route::get('/charts', [ChartController::class, 'gallery'])->name('charts.gallery');

// Geographic areas reference
Route::get('/geographic-areas', [GeographicAreasController::class, 'index'])->name('geographic-areas');

// Serve build assets with CORS headers
// Use Request to get the full path including slashes
Route::get('/build/{path?}', function (Illuminate\Http\Request $request) {
    // Get the full request path after /build/
    $fullPath = $request->path(); // e.g., "build/assets/dashboard.js"
    $path = substr($fullPath, 6); // Remove "build/" prefix
    
    if (empty($path)) {
        abort(404);
    }
    
    $filePath = public_path('build/' . $path);
    
    // Security: prevent directory traversal
    $realPath = realpath($filePath);
    $buildPath = realpath(public_path('build'));
    
    if (!$realPath || !$buildPath || strpos($realPath, $buildPath) !== 0) {
        abort(404);
    }
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404);
    }
    
    $mimeType = mime_content_type($filePath);
    
    return Response::file($filePath, [
        'Content-Type' => $mimeType,
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type',
    ]);
})->where('path', '.*');
