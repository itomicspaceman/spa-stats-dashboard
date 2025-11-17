<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\VenueCategoryUpdater;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixIncorrectCategories extends Command
{
    protected $signature = 'venues:fix-categories
                            {--dry-run : Preview changes without updating database}';

    protected $description = 'Fix specific venues that were incorrectly categorized';

    protected VenueCategoryUpdater $updater;

    public function __construct(VenueCategoryUpdater $updater)
    {
        parent::__construct();
        $this->updater = $updater;
    }
    
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

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Venues that need to be fixed
        // Format: venue_id => [new_category_id, venue_name]
        $fixes = [
            // Gold Coast Squash Centre - should be Dedicated facility (5), not Leisure centre (2)
            // We'll search by name pattern since we don't know the exact ID
            // Eastside Squash - should be Dedicated facility (5), not Leisure centre (2)
        ];

        $this->info('🔧 Fixing Incorrectly Categorized Venues');
        $this->newLine();

        // Find venues by name pattern or ID (from earlier test runs)
        // Venue #158: Gold Coast Squash Centre
        // Venue #130: Eastside Squash - Entry by Appointment
        
        $goldCoast = Venue::where(function($query) {
                $query->where('name', 'like', '%Gold Coast%Squash%')
                      ->orWhere('name', 'like', '%Labrador%Squash%')
                      ->orWhere('id', 158);
            })
            ->first();

        $eastside = Venue::where(function($query) {
                $query->where('name', 'like', '%Eastside%Squash%')
                      ->orWhere('id', 130);
            })
            ->first();

        $venuesToFix = [];
        if ($goldCoast) {
            $venuesToFix[] = [
                'venue' => $goldCoast,
                'new_category_id' => 5, // Dedicated facility
                'reason' => 'Gold Coast Squash Centre - incorrectly categorized as Leisure centre, should be Dedicated facility',
            ];
        }
        if ($eastside) {
            $venuesToFix[] = [
                'venue' => $eastside,
                'new_category_id' => 5, // Dedicated facility
                'reason' => 'Eastside Squash - incorrectly categorized as Leisure centre, should be Dedicated facility',
            ];
        }

        if (empty($venuesToFix)) {
            $this->info('✅ No venues found.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($venuesToFix) . ' venue(s):');
        $this->newLine();

        foreach ($venuesToFix as $fix) {
            $venue = $fix['venue'];
            $categoryName = $this->getCategoryName($venue->category_id);
            $this->line("Venue #{$venue->id}: {$venue->name}");
            $this->line("  Current category: {$venue->category_id} ({$categoryName})");
            $this->line("  New category: {$fix['new_category_id']} (Dedicated facility)");
            $this->line("  Reason: {$fix['reason']}");
            $this->newLine();
        }
        
        // Only fix if current category is NOT already Dedicated facility
        $venuesToFix = array_filter($venuesToFix, function($fix) {
            return $fix['venue']->category_id !== $fix['new_category_id'];
        });
        
        if (empty($venuesToFix)) {
            $this->info('✅ All venues are already correctly categorized.');
            return self::SUCCESS;
        }
        
        $this->info('Will fix ' . count($venuesToFix) . ' venue(s):');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN - No changes will be made');
            return self::SUCCESS;
        }

        if (!$this->confirm('Update these venues?', true)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();
        $updated = 0;
        $failed = 0;

        foreach ($venuesToFix as $fix) {
            $venue = $fix['venue'];
            $result = $this->updater->updateVenue(
                $venue->id,
                $fix['new_category_id'],
                [
                    'confidence' => 'HIGH',
                    'reasoning' => $fix['reason'],
                    'source' => 'MANUAL',
                    'google_places_data' => null,
                    'matched_type' => 'manual_fix',
                ]
            );

            if ($result['success']) {
                $this->info("✅ Updated venue #{$venue->id}: {$venue->name}");
                $updated++;
            } else {
                $this->error("❌ Failed to update venue #{$venue->id}: {$result['message']}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$updated} updated, {$failed} failed");

        return self::SUCCESS;
    }
}

