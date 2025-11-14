<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\GooglePlacesService;
use Illuminate\Console\Command;

class CheckInvalidPlaceIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:check-place-ids
                            {--batch=50 : Number of venues to check}
                            {--export= : Export results to file (csv or json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for venues with invalid or expired Google Place IDs';

    protected GooglePlacesService $googlePlacesService;

    public function __construct(GooglePlacesService $googlePlacesService)
    {
        parent::__construct();
        $this->googlePlacesService = $googlePlacesService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking Google Place IDs for validity...');
        $this->newLine();

        $batchSize = (int) $this->option('batch');

        // Get venues with "Don't know" category that have Place IDs
        $venues = Venue::on('squash_remote')
            ->where('status', '1')
            ->where('category_id', 6)
            ->whereNotNull('g_place_id')
            ->where('g_place_id', '!=', '')
            ->limit($batchSize)
            ->get();

        $this->info("Checking {$venues->count()} venues...");
        $this->newLine();

        $results = [
            'valid' => [],
            'invalid' => [],
            'errors' => [],
        ];

        $progressBar = $this->output->createProgressBar($venues->count());
        $progressBar->start();

        foreach ($venues as $venue) {
            try {
                $placeData = $this->googlePlacesService->getPlaceDetails($venue->g_place_id);

                if ($placeData) {
                    $results['valid'][] = [
                        'venue_id' => $venue->id,
                        'venue_name' => $venue->name,
                        'g_place_id' => $venue->g_place_id,
                        'status' => 'Valid',
                    ];
                } else {
                    $results['invalid'][] = [
                        'venue_id' => $venue->id,
                        'venue_name' => $venue->name,
                        'g_place_id' => $venue->g_place_id,
                        'status' => 'Invalid/Expired',
                    ];
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'g_place_id' => $venue->g_place_id,
                    'status' => 'Error',
                    'error' => $e->getMessage(),
                ];
            }

            $progressBar->advance();
            sleep(1); // Rate limit
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($results);

        // Export if requested
        $exportFormat = $this->option('export');
        if ($exportFormat) {
            $this->exportResults($results, $exportFormat);
        }

        return self::SUCCESS;
    }

    /**
     * Display summary of results.
     */
    protected function displaySummary(array $results): void
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 SUMMARY');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $validCount = count($results['valid']);
        $invalidCount = count($results['invalid']);
        $errorCount = count($results['errors']);
        $total = $validCount + $invalidCount + $errorCount;

        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['✅ Valid Place IDs', $validCount, $this->percentage($validCount, $total)],
                ['❌ Invalid/Expired Place IDs', $invalidCount, $this->percentage($invalidCount, $total)],
                ['⚠️  Errors', $errorCount, $this->percentage($errorCount, $total)],
                ['Total', $total, '100%'],
            ]
        );

        // Show invalid venues
        if ($invalidCount > 0) {
            $this->newLine();
            $this->warn('❌ Venues with Invalid/Expired Place IDs:');
            $this->newLine();

            $tableData = [];
            foreach ($results['invalid'] as $venue) {
                $tableData[] = [
                    $venue['venue_id'],
                    $this->truncate($venue['venue_name'], 40),
                    $venue['g_place_id'],
                ];
            }

            $this->table(
                ['Venue ID', 'Venue Name', 'Place ID'],
                $tableData
            );
        }

        // Show errors
        if ($errorCount > 0) {
            $this->newLine();
            $this->error('⚠️  Venues with Errors:');
            $this->newLine();

            $tableData = [];
            foreach ($results['errors'] as $venue) {
                $tableData[] = [
                    $venue['venue_id'],
                    $this->truncate($venue['venue_name'], 30),
                    $this->truncate($venue['error'], 40),
                ];
            }

            $this->table(
                ['Venue ID', 'Venue Name', 'Error'],
                $tableData
            );
        }
    }

    /**
     * Export results to file.
     */
    protected function exportResults(array $results, string $format): void
    {
        $format = strtolower($format);

        if (!in_array($format, ['csv', 'json'])) {
            $this->error("Invalid export format. Use 'csv' or 'json'.");
            return;
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "invalid-place-ids_{$timestamp}.{$format}";
        $filepath = storage_path("logs/{$filename}");

        try {
            if ($format === 'csv') {
                $this->exportToCsv($results, $filepath);
            } else {
                $this->exportToJson($results, $filepath);
            }

            $this->newLine();
            $this->info("📄 Results exported to: {$filepath}");
        } catch (\Exception $e) {
            $this->error("Failed to export results: {$e->getMessage()}");
        }
    }

    /**
     * Export to CSV.
     */
    protected function exportToCsv(array $results, string $filepath): void
    {
        $handle = fopen($filepath, 'w');

        fputcsv($handle, ['Venue ID', 'Venue Name', 'Google Place ID', 'Status', 'Error']);

        foreach ($results['valid'] as $venue) {
            fputcsv($handle, [
                $venue['venue_id'],
                $venue['venue_name'],
                $venue['g_place_id'],
                'Valid',
                '',
            ]);
        }

        foreach ($results['invalid'] as $venue) {
            fputcsv($handle, [
                $venue['venue_id'],
                $venue['venue_name'],
                $venue['g_place_id'],
                'Invalid/Expired',
                '',
            ]);
        }

        foreach ($results['errors'] as $venue) {
            fputcsv($handle, [
                $venue['venue_id'],
                $venue['venue_name'],
                $venue['g_place_id'],
                'Error',
                $venue['error'],
            ]);
        }

        fclose($handle);
    }

    /**
     * Export to JSON.
     */
    protected function exportToJson(array $results, string $filepath): void
    {
        $data = [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'valid_count' => count($results['valid']),
                'invalid_count' => count($results['invalid']),
                'error_count' => count($results['errors']),
            ],
            'results' => $results,
        ];

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Calculate percentage.
     */
    protected function percentage(int $count, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }
        return round(($count / $total) * 100, 1) . '%';
    }

    /**
     * Truncate string.
     */
    protected function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length - 3) . '...';
    }
}

