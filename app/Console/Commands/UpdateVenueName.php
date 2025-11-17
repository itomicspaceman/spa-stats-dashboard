<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\GooglePlacesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateVenueName extends Command
{
    protected $signature = 'venues:update-name
                            {venue_id : The venue ID to update}
                            {--dry-run : Preview changes without updating database}';

    protected $description = 'Update a venue name from Google Places (Google is authoritative)';

    protected GooglePlacesService $googlePlacesService;

    public function __construct(GooglePlacesService $googlePlacesService)
    {
        parent::__construct();
        $this->googlePlacesService = $googlePlacesService;
    }

    public function handle(): int
    {
        $venueId = (int) $this->argument('venue_id');
        $dryRun = $this->option('dry-run');

        $venue = Venue::find($venueId);
        
        if (!$venue) {
            $this->error("Venue #{$venueId} not found");
            return self::FAILURE;
        }

        if (empty($venue->g_place_id)) {
            $this->error("Venue #{$venueId} has no Google Place ID");
            return self::FAILURE;
        }

        $this->info("Fetching Google Places data for venue #{$venueId}...");
        $result = $this->googlePlacesService->getPlaceDetails($venue->g_place_id, 'en');

        if (!$result['success']) {
            $this->error("Failed to fetch Google Places data: {$result['error']}");
            return self::FAILURE;
        }

        $googleName = $result['data']['displayName'] ?? null;

        if (!$googleName) {
            $this->error("Google Places did not return a display name");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Current name: {$venue->name}");
        $this->info("Google Places name: {$googleName}");
        $this->newLine();

        if ($googleName === $venue->name) {
            $this->info("✅ Names match - no update needed");
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("🔍 DRY RUN - Would update name to: {$googleName}");
            return self::SUCCESS;
        }

        if (!$this->confirm("Update venue name?", true)) {
            $this->info("Cancelled.");
            return self::SUCCESS;
        }

        DB::connection('squash_remote')->table('venues')
            ->where('id', $venueId)
            ->update([
                'name' => $googleName,
                'updated_at' => now(),
            ]);

        Log::info("Updated venue name from Google Places", [
            'venue_id' => $venueId,
            'old_name' => $venue->name,
            'new_name' => $googleName,
        ]);

        $this->info("✅ Updated venue #{$venueId} name to: {$googleName}");

        return self::SUCCESS;
    }
}


