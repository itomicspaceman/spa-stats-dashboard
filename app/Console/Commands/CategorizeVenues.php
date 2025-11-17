<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\VenueCategorizer;
use App\Services\VenueCategoryUpdater;
use App\Services\NewCategoryDetector;
use Illuminate\Console\Command;

class CategorizeVenues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:categorize
                            {--batch=5 : Number of venues to process}
                            {--dry-run : Preview recommendations without updating database}
                            {--include-other : Also process "Other" category venues}
                            {--min-confidence=MEDIUM : Minimum confidence level to auto-update (HIGH/MEDIUM/LOW)}
                            {--no-ai : Disable AI fallback for low confidence mappings}
                            {--export= : Export report to file (csv or json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically categorize venues using Google Places API and AI';

    protected VenueCategorizer $categorizer;
    protected VenueCategoryUpdater $updater;
    protected NewCategoryDetector $detector;

    public function __construct(
        VenueCategorizer $categorizer,
        VenueCategoryUpdater $updater,
        NewCategoryDetector $detector
    ) {
        parent::__construct();
        $this->categorizer = $categorizer;
        $this->updater = $updater;
        $this->detector = $detector;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏸 Squash Venue Categorization System');
        $this->newLine();

        // Get options
        $batchSize = (int) $this->option('batch');
        $dryRun = $this->option('dry-run');
        $includeOther = $this->option('include-other');
        $minConfidence = strtoupper($this->option('min-confidence'));
        $useAI = !$this->option('no-ai');

        // Validate min-confidence
        if (!in_array($minConfidence, ['HIGH', 'MEDIUM', 'LOW'])) {
            $this->error('Invalid min-confidence value. Must be HIGH, MEDIUM, or LOW.');
            return self::FAILURE;
        }

        // Show statistics
        $this->showStats($includeOther);
        $this->newLine();

        // Confirm if not dry-run
        if (!$dryRun) {
            if (!$this->confirm('This will update the database. Continue?', false)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
            $this->newLine();
        } else {
            $this->warn('🔍 DRY RUN MODE - No database changes will be made');
            $this->newLine();
        }

        // Process venues
        $this->info("Processing {$batchSize} venues...");
        $this->info("AI Fallback: " . ($useAI ? 'Enabled' : 'Disabled'));
        $this->info("Minimum Confidence: {$minConfidence}");
        $this->newLine();

        $results = $this->categorizer->processBatch($batchSize, $includeOther, $useAI);

        // Display results
        $this->displayResults($results, $dryRun, $minConfidence);

        // Show mapping summary
        $this->showMappingSummary($results);

        // Export if requested
        $exportFormat = $this->option('export');
        if ($exportFormat) {
            $this->exportReport($results, $exportFormat);
        }

        // Update database if not dry-run
        if (!$dryRun) {
            $this->updateDatabase($results, $minConfidence);
        }

        // Show new category suggestions
        $this->showNewCategorySuggestions();

        return self::SUCCESS;
    }

    /**
     * Show statistics about venues needing categorization.
     */
    protected function showStats(bool $includeOther): void
    {
        $stats = $this->categorizer->getStats($includeOther);

        $this->info('📊 Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total needing categorization', number_format($stats['total_needing_categorization'])],
                ['"Don\'t know" category', number_format($stats['dont_know_count'])],
                ['"Other" category', number_format($stats['other_count'])],
                ['With Google Place ID', number_format($stats['with_place_id'])],
                ['Without Google Place ID', number_format($stats['without_place_id'])],
                ['Processable', number_format($stats['processable'])],
            ]
        );
    }

    /**
     * Display categorization results.
     */
    protected function displayResults(array $results, bool $dryRun, string $minConfidence): void
    {
        foreach ($results as $index => $result) {
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Venue #{$result['venue_id']}: {$result['venue_name']}");
            $this->line("Address: {$result['venue_address']}");
            $this->line("Google Place ID: {$result['g_place_id']}");
            
            // Show Place ID refresh if it occurred
            if ($result['place_id_refreshed'] ?? false) {
                $this->warn("🔄 Place ID refreshed via {$result['place_id_refresh_source']}");
            }
            
            // Show name update if it occurred
            if ($result['name_updated'] ?? false) {
                $this->line("<fg=cyan>📝 Name updated: '{$result['old_name']}' → '{$result['new_name']}'</>");
            }
            
            // Show if venue was flagged for deletion
            if ($result['venue_flagged_for_deletion'] ?? false) {
                $this->error("🗑️  Venue flagged for deletion - Place ID expired and venue not found");
            }
            
            if ($result['error']) {
                $this->error("❌ Error: {$result['error']}");
                continue;
            }

            // Google Places data
            if ($result['google_places_data']) {
                $data = $result['google_places_data'];
                $this->line("Primary Type: " . ($data['primaryType'] ?? 'None'));
                $this->line("All Types: " . implode(', ', $data['types'] ?? []));
            }

            // Show context analysis if performed
            if ($result['context_analyzed'] ?? false) {
                if ($result['is_sub_venue'] ?? false) {
                    $this->line("<fg=cyan>🔍 Context: Sub-venue detected</>");
                    if ($result['context_adjusted'] ?? false) {
                        $this->line("<fg=cyan>   ↳ Category adjusted based on context</>");
                    }
                }
            }

            // Recommendation
            $confidence = $result['confidence'];
            $confidenceColor = match($confidence) {
                'HIGH' => 'green',
                'MEDIUM' => 'yellow',
                'LOW' => 'red',
                default => 'white',
            };

            if ($result['recommended_category_id']) {
                $categoryName = $this->getCategoryName($result['recommended_category_id']);
                $this->line("<fg={$confidenceColor}>Recommended Category: {$categoryName} (ID: {$result['recommended_category_id']})</>");
                $this->line("<fg={$confidenceColor}>Confidence: {$confidence}</>");
                $this->line("Source: {$result['source']}");
                $this->line("Reasoning: {$result['reasoning']}");

                // Check if will be updated
                $willUpdate = $this->shouldUpdate($confidence, $minConfidence);
                if (!$dryRun && $willUpdate) {
                    $this->info("✅ Will be updated");
                } elseif (!$dryRun && !$willUpdate) {
                    $this->warn("⚠️  Confidence too low - flagged for manual review");
                } else {
                    $this->line("🔍 Dry run - no changes");
                }
            } else {
                $this->error("❌ No category recommendation");
            }

            // New category suggestion
            if ($result['suggest_new_category'] && $result['suggested_category_name']) {
                $this->warn("💡 Suggests new category: {$result['suggested_category_name']}");
            }

            // Show court count search if performed
            if ($result['court_count_searched'] ?? false) {
                $this->newLine();
                $this->line("<fg=magenta>🏸 Court Count Search:</>");
                
                if ($result['court_count_found'] !== null) {
                    $courtConfidence = $result['court_count_confidence'] ?? 'LOW';
                    $courtConfidenceColor = match($courtConfidence) {
                        'HIGH' => 'green',
                        'MEDIUM' => 'yellow',
                        'LOW' => 'red',
                        default => 'white',
                    };
                    
                    $this->line("<fg={$courtConfidenceColor}>   Found: {$result['court_count_found']} court(s) ({$courtConfidence} confidence)</>");
                    
                    if ($result['court_count_updated'] ?? false) {
                        $this->info("   ✅ Court count updated in database");
                    } else {
                        $this->warn("   ⚠️  Confidence too low - not updated");
                    }
                } else {
                    if ($result['court_count_flagged_for_deletion'] ?? false) {
                        $this->error("   🗑️  No evidence of squash courts found - venue flagged for deletion");
                    } else {
                        // Check if we have evidence but couldn't determine count
                        // This happens when evidence_found = true but court_count = null
                        $this->warn("   ⚠️  Could not determine court count");
                        $this->line("   ℹ️  Note: Evidence of squash courts was found, but exact count could not be determined");
                        $this->line("   ℹ️  Venue will NOT be flagged for deletion (evidence exists)");
                    }
                }
            }
        }
    }

    /**
     * Update database with categorization results.
     */
    protected function updateDatabase(array $results, string $minConfidence): void
    {
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📝 Updating Database...');
        $this->newLine();

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($results as $result) {
            if ($result['error'] || !$result['recommended_category_id']) {
                // Touch the venue's updated_at timestamp so it moves to the back of the queue
                Venue::where('id', $result['venue_id'])->update(['updated_at' => now()]);
                $skipped++;
                continue;
            }

            $confidence = $result['confidence'];
            if (!$this->shouldUpdate($confidence, $minConfidence)) {
                $this->line("⏭️  Skipped venue #{$result['venue_id']} - confidence too low ({$confidence})");
                // Touch the venue's updated_at timestamp so it moves to the back of the queue
                // This prevents re-processing the same low-confidence venues repeatedly
                Venue::where('id', $result['venue_id'])->update(['updated_at' => now()]);
                $skipped++;
                continue;
            }

            $updateResult = $this->updater->updateVenue(
                $result['venue_id'],
                $result['recommended_category_id'],
                $result
            );

            if ($updateResult['success']) {
                $categoryName = $this->getCategoryName($result['recommended_category_id']);
                $this->info("✅ Updated venue #{$result['venue_id']} to '{$categoryName}' ({$confidence})");
                $updated++;
            } else {
                $this->error("❌ Failed to update venue #{$result['venue_id']}: {$updateResult['message']}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$updated} updated, {$skipped} skipped, {$failed} failed");
    }

    /**
     * Show mapping summary report.
     */
    protected function showMappingSummary(array $results): void
    {
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 DETAILED MAPPING REPORT');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Build table data
        $tableData = [];
        foreach ($results as $result) {
            if ($result['error']) {
                $tableData[] = [
                    $result['venue_id'],
                    $this->truncate($result['venue_name'], 25),
                    'ERROR',
                    '-',
                    '-',
                    '-',
                    $this->truncate($result['error'], 30),
                ];
                continue;
            }

            $categoryName = $result['recommended_category_id'] 
                ? $this->getCategoryName($result['recommended_category_id'])
                : 'None';

            $primaryType = $result['google_places_data']['primaryType'] ?? 'unknown';
            
            // Get all types (limit to first 3 for display)
            $allTypes = $result['google_places_data']['types'] ?? [];
            $typesDisplay = count($allTypes) > 3 
                ? implode(', ', array_slice($allTypes, 0, 3)) . '... (+' . (count($allTypes) - 3) . ')'
                : implode(', ', $allTypes);

            $tableData[] = [
                $result['venue_id'],
                $this->truncate($result['venue_name'], 25),
                $this->truncate($categoryName, 20),
                $result['confidence'],
                $result['source'] === 'GOOGLE_MAPPING' ? 'Google' : 'AI',
                $primaryType,
                $this->truncate($result['reasoning'], 40),
            ];
        }

        $this->table(
            ['ID', 'Venue Name', 'Category', 'Conf', 'Source', 'Primary Type', 'Reasoning'],
            $tableData
        );

        $this->newLine();
        $this->showAggregateStats($results);

    }

    /**
     * Show aggregate statistics.
     */
    protected function showAggregateStats(array $results): void
    {
        // Count by category
        $byCategory = [];
        $byConfidence = ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];
        $bySource = ['GOOGLE_MAPPING' => 0, 'OPENAI' => 0];
        $errors = 0;
        $googleTypes = [];
        $patternCounts = [];
        $placeIdRefreshed = 0;
        $placeIdRefreshSources = ['Google (free)' => 0, 'Text Search' => 0];
        $venuesFlaggedForDeletion = 0;

        foreach ($results as $result) {
            // Track Place ID refreshes
            if ($result['place_id_refreshed'] ?? false) {
                $placeIdRefreshed++;
                $source = $result['place_id_refresh_source'] ?? 'Unknown';
                $placeIdRefreshSources[$source] = ($placeIdRefreshSources[$source] ?? 0) + 1;
            }
            
            // Track venues flagged for deletion
            if ($result['venue_flagged_for_deletion'] ?? false) {
                $venuesFlaggedForDeletion++;
            }
            
            if ($result['error']) {
                $errors++;
                continue;
            }

            // Count by category
            if ($result['recommended_category_id']) {
                $categoryName = $this->getCategoryName($result['recommended_category_id']);
                $byCategory[$categoryName] = ($byCategory[$categoryName] ?? 0) + 1;
            }

            // Count by confidence
            if (isset($byConfidence[$result['confidence']])) {
                $byConfidence[$result['confidence']]++;
            }

            // Count by source
            if ($result['source'] && isset($bySource[$result['source']])) {
                $bySource[$result['source']]++;
            }

            // Track Google Places types
            if ($result['google_places_data']) {
                $primaryType = $result['google_places_data']['primaryType'] ?? 'unknown';
                $googleTypes[$primaryType] = ($googleTypes[$primaryType] ?? 0) + 1;
            }

            // Track patterns
            if ($result['matched_type']) {
                $pattern = $result['matched_type'];
                $patternCounts[$pattern] = ($patternCounts[$pattern] ?? 0) + 1;
            }
        }

        $this->info('📈 AGGREGATE STATISTICS');
        $this->newLine();
        
        // Show Place ID refresh statistics if any occurred
        if ($placeIdRefreshed > 0) {
            $this->line('<fg=cyan>Place ID Refresh:</>');
            $this->line("  • Total refreshed: {$placeIdRefreshed} venue(s)");
            foreach ($placeIdRefreshSources as $source => $count) {
                if ($count > 0) {
                    $this->line("    - via {$source}: {$count} venue(s)");
                }
            }
            $this->newLine();
        }
        
        // Show deletion statistics if any occurred
        if ($venuesFlaggedForDeletion > 0) {
            $this->line('<fg=red>Venues Flagged for Deletion:</>');
            $this->line("  • {$venuesFlaggedForDeletion} venue(s) flagged (expired Place ID, not found)");
            $this->newLine();
        }

        // Display category breakdown
        $this->line('<fg=cyan>Categories Assigned:</>');
        if (empty($byCategory)) {
            $this->line('  None');
        } else {
            arsort($byCategory);
            foreach ($byCategory as $category => $count) {
                $this->line("  • {$category}: {$count} venue(s)");
            }
        }

        $this->newLine();

        // Display confidence breakdown
        $this->line('<fg=cyan>Confidence Levels:</>');
        $this->table(
            ['Confidence', 'Count', 'Percentage'],
            [
                ['HIGH', $byConfidence['HIGH'], $this->percentage($byConfidence['HIGH'], count($results))],
                ['MEDIUM', $byConfidence['MEDIUM'], $this->percentage($byConfidence['MEDIUM'], count($results))],
                ['LOW', $byConfidence['LOW'], $this->percentage($byConfidence['LOW'], count($results))],
            ]
        );

        // Display source breakdown
        $this->line('<fg=cyan>Categorization Source:</>');
        $this->table(
            ['Source', 'Count', 'Percentage'],
            [
                ['Google Places Mapping', $bySource['GOOGLE_MAPPING'], $this->percentage($bySource['GOOGLE_MAPPING'], count($results))],
                ['AI (OpenAI)', $bySource['OPENAI'], $this->percentage($bySource['OPENAI'], count($results))],
                ['Errors', $errors, $this->percentage($errors, count($results))],
            ]
        );

        // Display Google Places types encountered
        $this->line('<fg=cyan>Google Places Primary Types Encountered:</>');
        if (empty($googleTypes)) {
            $this->line('  None');
        } else {
            arsort($googleTypes);
            $topTypes = array_slice($googleTypes, 0, 10, true);
            foreach ($topTypes as $type => $count) {
                $this->line("  • {$type}: {$count} venue(s)");
            }
            if (count($googleTypes) > 10) {
                $remaining = count($googleTypes) - 10;
                $this->line("  ... and {$remaining} more type(s)");
            }
        }

        // Display mapping patterns used
        $this->newLine();
        $this->line('<fg=cyan>Mapping Patterns:</>');
        if (empty($patternCounts)) {
            $this->line('  None');
        } else {
            arsort($patternCounts);
            foreach ($patternCounts as $pattern => $count) {
                $description = $this->describePattern($pattern);
                $this->line("  • {$description}: {$count} venue(s)");
            }
        }
    }

    /**
     * Truncate string for table display.
     */
    protected function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length - 3) . '...';
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
     * Describe a mapping pattern in human-readable form.
     */
    protected function describePattern(string $pattern): string
    {
        $descriptions = [
            'gym+swimming_pool' => 'Gym with pool (combination)',
            'gym+sports_facilities' => 'Gym with sports facilities (combination)',
            'country_club' => 'Country club (combination)',
            'private_club' => 'Private club (combination)',
            'office+sports' => 'Office with sports (combination)',
            'industrial+sports' => 'Industrial with sports (combination)',
            'hotel+sports' => 'Hotel with sports (combination)',
            'school+sports' => 'School with sports (combination)',
            'university+sports' => 'University with sports (combination)',
        ];

        return $descriptions[$pattern] ?? $pattern;
    }

    /**
     * Show new category suggestions.
     */
    protected function showNewCategorySuggestions(): void
    {
        $summary = $this->detector->getSummary();

        if ($summary['unmapped_type_groups'] > 0 || $summary['ai_suggested_categories'] > 0) {
            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->warn('💡 New Category Suggestions Detected');
            $this->newLine();

            $report = $this->detector->generateReport();
            $reportPath = storage_path('logs/suggested-new-categories.json');
            
            $this->line("Unmapped Google Places type groups: {$summary['unmapped_type_groups']}");
            $this->line("AI suggested new categories: {$summary['ai_suggested_categories']}");
            $this->line("Total unmapped venues: {$summary['total_unmapped_venues']}");
            $this->newLine();
            $this->info("📄 Full report saved to: {$reportPath}");

            // Show top suggestions
            if (!empty($report['ai_suggested_categories'])) {
                $this->newLine();
                $this->line("Top AI Suggestions:");
                foreach (array_slice($report['ai_suggested_categories'], 0, 3) as $suggestion) {
                    $this->line("  • {$suggestion['suggested_category_name']} ({$suggestion['venue_count']} venues)");
                }
            }
        }
    }

    /**
     * Determine if a venue should be updated based on confidence level.
     */
    protected function shouldUpdate(string $confidence, string $minConfidence): bool
    {
        $levels = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3];
        return $levels[$confidence] >= $levels[$minConfidence];
    }

    /**
     * Export report to file.
     */
    protected function exportReport(array $results, string $format): void
    {
        $format = strtolower($format);
        
        if (!in_array($format, ['csv', 'json'])) {
            $this->error("Invalid export format. Use 'csv' or 'json'.");
            return;
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "venue-categorization-report_{$timestamp}.{$format}";
        $filepath = storage_path("logs/{$filename}");

        try {
            if ($format === 'csv') {
                $this->exportToCsv($results, $filepath);
            } else {
                $this->exportToJson($results, $filepath);
            }

            $this->newLine();
            $this->info("📄 Report exported to: {$filepath}");
        } catch (\Exception $e) {
            $this->error("Failed to export report: {$e->getMessage()}");
        }
    }

    /**
     * Export results to CSV.
     */
    protected function exportToCsv(array $results, string $filepath): void
    {
        $handle = fopen($filepath, 'w');
        
        // Write header
        fputcsv($handle, [
            'Venue ID',
            'Venue Name',
            'Address',
            'Google Place ID',
            'Recommended Category',
            'Category ID',
            'Confidence',
            'Source',
            'Primary Type',
            'All Types',
            'Reasoning',
            'Matched Pattern',
            'Error',
        ]);

        // Write data
        foreach ($results as $result) {
            $allTypes = isset($result['google_places_data']['types']) 
                ? implode('; ', $result['google_places_data']['types'])
                : '';

            fputcsv($handle, [
                $result['venue_id'],
                $result['venue_name'],
                $result['venue_address'],
                $result['g_place_id'],
                $result['recommended_category_id'] ? $this->getCategoryName($result['recommended_category_id']) : '',
                $result['recommended_category_id'] ?? '',
                $result['confidence'],
                $result['source'] ?? '',
                $result['google_places_data']['primaryType'] ?? '',
                $allTypes,
                $result['reasoning'],
                $result['matched_type'] ?? '',
                $result['error'] ?? '',
            ]);
        }

        fclose($handle);
    }

    /**
     * Export results to JSON.
     */
    protected function exportToJson(array $results, string $filepath): void
    {
        $exportData = [
            'generated_at' => now()->toIso8601String(),
            'total_venues' => count($results),
            'results' => array_map(function ($result) {
                return [
                    'venue_id' => $result['venue_id'],
                    'venue_name' => $result['venue_name'],
                    'address' => $result['venue_address'],
                    'g_place_id' => $result['g_place_id'],
                    'current_category_id' => $result['current_category_id'],
                    'recommended_category_id' => $result['recommended_category_id'],
                    'recommended_category_name' => $result['recommended_category_id'] 
                        ? $this->getCategoryName($result['recommended_category_id']) 
                        : null,
                    'confidence' => $result['confidence'],
                    'source' => $result['source'],
                    'google_places_data' => $result['google_places_data'],
                    'matched_type' => $result['matched_type'],
                    'reasoning' => $result['reasoning'],
                    'suggest_new_category' => $result['suggest_new_category'],
                    'suggested_category_name' => $result['suggested_category_name'],
                    'error' => $result['error'],
                ];
            }, $results),
        ];

        file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT));
    }

    /**
     * Get category name by ID.
     */
    protected function getCategoryName(int $categoryId): string
    {
        $categories = [
            1 => 'Other',
            2 => 'Leisure centre',
            3 => 'School',
            4 => 'Gym or health & fitness centre',
            5 => 'Dedicated facility',
            6 => 'Don\'t know',
            7 => 'Hotel or resort',
            8 => 'College or university',
            9 => 'Military',
            10 => 'Shopping centre',
            11 => 'Community hall',
            12 => 'Private residence',
            13 => 'Business complex',
            14 => 'Private club',
            15 => 'Country club',
            16 => 'Industrial',
        ];

        return $categories[$categoryId] ?? "Unknown (ID: {$categoryId})";
    }
}
