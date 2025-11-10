<?php

namespace App\Console\Commands;

use App\Services\SquashDataAggregator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyncSquashDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'squash:sync {--force : Force sync even if cache is fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync squash dashboard data from remote database and populate cache';

    /**
     * Execute the console command.
     */
    public function handle(SquashDataAggregator $aggregator)
    {
        $startTime = now();
        $this->info('Starting squash dashboard sync...');

        // Create sync log entry
        $logId = DB::table('squash_sync_logs')->insertGetId([
            'started_at' => $startTime,
            'status' => 'running',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $cacheKeys = [];
            $ttl = 10800; // 3 hours in seconds

            // 1. Country Stats
            $this->info('Aggregating country stats...');
            $countryStats = $aggregator->countryStats();
            Cache::put('squash:country_stats', $countryStats, $ttl);
            $cacheKeys[] = 'squash:country_stats';

            // Export to JSON for inspection
            Storage::disk('local')->put('dashboard/country_stats.json', json_encode($countryStats, JSON_PRETTY_PRINT));

            // 2. Top Countries (multiple metrics)
            foreach (['venues', 'courts', 'glass_courts', 'outdoor_courts'] as $metric) {
                $this->info("Aggregating top countries by {$metric}...");
                $topCountries = $aggregator->topCountriesBy($metric, 30);
                $key = "squash:top_countries:{$metric}:30";
                Cache::put($key, $topCountries, $ttl);
                $cacheKeys[] = $key;

                Storage::disk('local')->put("dashboard/top_countries_{$metric}.json", json_encode($topCountries, JSON_PRETTY_PRINT));
            }

            // 3. Court Distribution
            $this->info('Aggregating court distribution...');
            $courtDist = $aggregator->courtDistribution();
            Cache::put('squash:court_distribution', $courtDist, $ttl);
            $cacheKeys[] = 'squash:court_distribution';

            Storage::disk('local')->put('dashboard/court_distribution.json', json_encode($courtDist, JSON_PRETTY_PRINT));

            // 4. Timeline
            $this->info('Aggregating timeline data...');
            $timeline = $aggregator->timeline('monthly');
            Cache::put('squash:timeline:monthly', $timeline, $ttl);
            $cacheKeys[] = 'squash:timeline:monthly';

            Storage::disk('local')->put('dashboard/timeline.json', json_encode($timeline, JSON_PRETTY_PRINT));

            // 5. Venue Types
            $this->info('Aggregating venue types...');
            $venueTypes = $aggregator->venueTypes();
            Cache::put('squash:venue_types', $venueTypes, $ttl);
            $cacheKeys[] = 'squash:venue_types';

            Storage::disk('local')->put('dashboard/venue_types.json', json_encode($venueTypes, JSON_PRETTY_PRINT));

            // 6. Map Data
            $this->info('Generating map GeoJSON...');
            $mapData = $aggregator->mapPoints();
            Cache::put('squash:map_data', $mapData, $ttl);
            $cacheKeys[] = 'squash:map_data';

            Storage::disk('local')->put('dashboard/map_data.json', json_encode($mapData, JSON_PRETTY_PRINT));

            // 7. Regional Breakdown
            $this->info('Aggregating regional breakdown...');
            $regionalData = $aggregator->regionalBreakdown();
            Cache::put('squash:regional_breakdown', $regionalData, $ttl);
            $cacheKeys[] = 'squash:regional_breakdown';

            Storage::disk('local')->put('dashboard/regional_breakdown.json', json_encode($regionalData, JSON_PRETTY_PRINT));

            // 8. Court Types Breakdown
            $this->info('Aggregating court types breakdown...');
            $courtTypes = $aggregator->courtTypesBreakdown();
            Cache::put('squash:court_types', $courtTypes, $ttl);
            $cacheKeys[] = 'squash:court_types';

            Storage::disk('local')->put('dashboard/court_types.json', json_encode($courtTypes, JSON_PRETTY_PRINT));

            // 9. Top Countries Multi-Metric
            $this->info('Aggregating top countries multi-metric...');
            $topCountriesMulti = $aggregator->topCountriesMultiMetric(30);
            Cache::put('squash:top_countries_multi', $topCountriesMulti, $ttl);
            $cacheKeys[] = 'squash:top_countries_multi';

            Storage::disk('local')->put('dashboard/top_countries_multi.json', json_encode($topCountriesMulti, JSON_PRETTY_PRINT));

            $endTime = now();
            $duration = $endTime->diffInSeconds($startTime);

            // Update sync log
            DB::table('squash_sync_logs')
                ->where('id', $logId)
                ->update([
                    'completed_at' => $endTime,
                    'duration_seconds' => $duration,
                    'venues_count' => $countryStats['total_venues'],
                    'countries_count' => $countryStats['countries_with_venues'],
                    'cache_keys' => json_encode($cacheKeys),
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);

            $this->info("Sync completed successfully in {$duration} seconds!");
            $this->info("Cached {$countryStats['total_venues']} venues from {$countryStats['countries_with_venues']} countries.");
            $this->info("Cache keys populated: " . count($cacheKeys));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Update sync log with error
            DB::table('squash_sync_logs')
                ->where('id', $logId)
                ->update([
                    'completed_at' => now(),
                    'duration_seconds' => now()->diffInSeconds($startTime),
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            $this->error('Sync failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
