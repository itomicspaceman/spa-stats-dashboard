<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\VenueCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VenueCategorizer
{
    protected GooglePlacesService $googlePlacesService;
    protected GooglePlacesTypeMapper $typeMapper;
    protected OpenAICategorizer $openAICategorizer;
    protected NewCategoryDetector $newCategoryDetector;
    protected GooglePlacesTextSearchService $textSearchService;
    protected ?GoogleTranslateService $translateService;
    protected VenueContextAnalyzer $contextAnalyzer;

    public function __construct(
        GooglePlacesService $googlePlacesService,
        GooglePlacesTypeMapper $typeMapper,
        OpenAICategorizer $openAICategorizer,
        NewCategoryDetector $newCategoryDetector,
        GooglePlacesTextSearchService $textSearchService,
        VenueContextAnalyzer $contextAnalyzer,
        ?GoogleTranslateService $translateService = null
    ) {
        $this->googlePlacesService = $googlePlacesService;
        $this->typeMapper = $typeMapper;
        $this->openAICategorizer = $openAICategorizer;
        $this->newCategoryDetector = $newCategoryDetector;
        $this->textSearchService = $textSearchService;
        $this->contextAnalyzer = $contextAnalyzer;
        $this->translateService = $translateService;
        
        // Inject translate service into mapper if available
        if ($this->translateService && $this->translateService->isConfigured()) {
            $this->typeMapper->setTranslateService($this->translateService);
        }
    }

    /**
     * Categorize a single venue.
     *
     * @param Venue $venue
     * @param bool $useAIFallback Whether to use AI if mapping confidence is low
     * @return array
     */
    public function categorizeVenue(Venue $venue, bool $useAIFallback = true): array
    {
        $result = [
            'venue_id' => $venue->id,
            'venue_name' => $venue->name,
            'venue_address' => $venue->physical_address,
            'current_category_id' => $venue->category_id,
            'g_place_id' => $venue->g_place_id,
            'recommended_category_id' => null,
            'confidence' => 'LOW',
            'reasoning' => '',
            'source' => null,
            'google_places_data' => null,
            'matched_type' => null,
            'suggest_new_category' => false,
            'suggested_category_name' => null,
            'error' => null,
            'place_id_refreshed' => false,
            'place_id_refresh_source' => null,
            'venue_flagged_for_deletion' => false,
            'context_analyzed' => false,
            'is_sub_venue' => false,
            'context_adjusted' => false,
            'name_updated' => false,
            'old_name' => null,
            'new_name' => null,
        ];

        // Validate venue has Google Place ID
        if (empty($venue->g_place_id)) {
            $result['error'] = 'Venue has no Google Place ID';
            $result['reasoning'] = 'Cannot categorize without Google Place ID';
            return $result;
        }

        // Step 1: Fetch Google Places data (request English names if available)
        $googlePlacesResult = $this->googlePlacesService->getPlaceDetails($venue->g_place_id, 'en');
        
        if (!$googlePlacesResult['success']) {
            // Place ID might be expired - attempt refresh using Google's free method
            Log::info("Place Details failed for venue #{$venue->id}, attempting Place ID refresh");
            
            $newPlaceId = $this->googlePlacesService->refreshPlaceId($venue->g_place_id);
            
            if ($newPlaceId && $newPlaceId !== $venue->g_place_id) {
                // Google provided a new Place ID - update and retry
                Log::info("Google provided new Place ID for venue #{$venue->id}");
                $this->updateVenuePlaceId($venue, $newPlaceId, 'Google Place ID refresh (free)');
                $result['place_id_refreshed'] = true;
                $result['place_id_refresh_source'] = 'Google (free)';
                
                $googlePlacesResult = $this->googlePlacesService->getPlaceDetails($newPlaceId);
            } else {
                // Try Text Search as fallback
                Log::info("Attempting Text Search for venue #{$venue->id}");
                $newPlaceId = $this->textSearchService->findPlaceByNameAndAddress($venue);
                
                if ($newPlaceId) {
                    Log::info("Text Search found new Place ID for venue #{$venue->id}");
                    $this->updateVenuePlaceId($venue, $newPlaceId, 'Text Search');
                    $result['place_id_refreshed'] = true;
                    $result['place_id_refresh_source'] = 'Text Search';
                    
                    $googlePlacesResult = $this->googlePlacesService->getPlaceDetails($newPlaceId);
                } else {
                    // Venue not found - flag for deletion
                    Log::warning("Venue #{$venue->id} not found via Text Search - flagging for deletion");
                    $this->flagVenueForDeletion($venue);
                    $result['error'] = 'Place ID expired and venue not found - flagged for deletion';
                    $result['reasoning'] = 'Google Place ID expired, venue could not be found via Text Search';
                    $result['venue_flagged_for_deletion'] = true;
                    return $result;
                }
            }
            
            // Check if retry was successful
            if (!$googlePlacesResult['success']) {
                $result['error'] = $googlePlacesResult['error'];
                $result['reasoning'] = 'Failed to fetch Google Places data even after Place ID refresh';
                return $result;
            }
        }

        $googlePlacesData = $googlePlacesResult['data'];
        $result['google_places_data'] = $googlePlacesData;

        // Step 1.5: Update venue name if it differs from Google Places (Google is authoritative)
        $googleName = $googlePlacesData['displayName'] ?? null;
        if ($googleName && $googleName !== $venue->name) {
            $oldName = $venue->name; // Store old name before update
            $this->updateVenueName($venue, $googleName);
            $result['name_updated'] = true;
            $result['old_name'] = $oldName;
            $result['new_name'] = $googleName;
        } else {
            $result['name_updated'] = false;
        }

        // Step 2: Try mapping Google Places types to category
        $mappingResult = $this->typeMapper->mapToCategory($googlePlacesData);
        
        $result['recommended_category_id'] = $mappingResult['category_id'];
        $result['confidence'] = $mappingResult['confidence'];
        $result['reasoning'] = $mappingResult['reasoning'];
        $result['matched_type'] = $mappingResult['matched_type'];
        $result['source'] = 'GOOGLE_MAPPING';

        // Step 2.5: Analyze context for sub-venue relationships
        // This helps distinguish standalone dedicated facilities from sub-venues of larger complexes
        $contextAnalysis = $this->contextAnalyzer->analyzeContext($venue, $googlePlacesData);
        $result['context_analyzed'] = true;
        $result['is_sub_venue'] = $contextAnalysis['is_sub_venue'];
        
        // Adjust category if context indicates sub-venue relationship
        if ($contextAnalysis['is_sub_venue'] && $mappingResult['category_id'] !== null) {
            $adjustedResult = $this->contextAnalyzer->adjustCategoryForContext(
                $mappingResult['category_id'],
                $contextAnalysis
            );
            
            if ($adjustedResult['adjusted']) {
                $result['recommended_category_id'] = $adjustedResult['category_id'];
                $result['confidence'] = $adjustedResult['confidence'];
                $result['reasoning'] = $adjustedResult['reasoning'];
                $result['context_adjusted'] = true;
                
                Log::info("Context adjustment for venue #{$venue->id}", [
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'original_category' => $mappingResult['category_id'],
                    'adjusted_category' => $adjustedResult['category_id'],
                    'context_reasoning' => $contextAnalysis['reasoning'],
                ]);
            }
        }

        // Track unmapped types for new category detection
        if ($mappingResult['category_id'] === null || $mappingResult['confidence'] === 'LOW') {
            $this->newCategoryDetector->trackUnmappedVenue($venue, $googlePlacesData);
        }

        // Step 3: If confidence is LOW and AI fallback is enabled, use OpenAI
        if ($mappingResult['confidence'] === 'LOW' && $useAIFallback) {
            $availableCategories = VenueCategory::orderBy('name')->get(['id', 'name'])->toArray();
            
            $venueData = [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->physical_address . ', ' . $venue->suburb . ', ' . $venue->state,
                'g_place_id' => $venue->g_place_id,
            ];

            $aiResult = $this->openAICategorizer->categorizeVenue(
                $venueData,
                $googlePlacesData,
                $availableCategories
            );

            // Use AI result if it provides a category
            if ($aiResult['category_id'] !== null) {
                $result['recommended_category_id'] = $aiResult['category_id'];
                $result['confidence'] = $aiResult['confidence'];
                $result['reasoning'] = 'AI: ' . $aiResult['reasoning'];
                $result['source'] = 'OPENAI';
            }

            // Track new category suggestions
            if ($aiResult['suggest_new_category']) {
                $result['suggest_new_category'] = true;
                $result['suggested_category_name'] = $aiResult['suggested_category_name'];
                $this->newCategoryDetector->trackNewCategorySuggestion(
                    $venue,
                    $googlePlacesData,
                    $aiResult['suggested_category_name']
                );
            }
        }

        return $result;
    }

    /**
     * Process a batch of venues.
     *
     * @param int $limit Number of venues to process
     * @param bool $includeOther Whether to include "Other" category venues
     * @param bool $useAIFallback Whether to use AI for low confidence mappings
     * @return array
     */
    public function processBatch(int $limit = 5, bool $includeOther = false, bool $useAIFallback = true): array
    {
        $query = Venue::where('status', '1')
            ->whereNotNull('g_place_id')
            ->where('g_place_id', '!=', '');

        if ($includeOther) {
            // Include both "Don't know" (6) and "Other" (1)
            $query->whereIn('category_id', [1, 6]);
        } else {
            // Only "Don't know" (6)
            $query->where('category_id', 6);
        }

        // Order by updated_at ascending to prioritize venues that haven't been processed recently
        // This helps avoid re-processing the same low-confidence venues repeatedly
        $venues = $query->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($venues as $venue) {
            $results[] = $this->categorizeVenue($venue, $useAIFallback);
            
            // Rate limiting: 1 second delay between API calls
            if ($venue !== $venues->last()) {
                sleep(1);
            }
        }

        return $results;
    }

    /**
     * Get statistics about venues needing categorization.
     *
     * @param bool $includeOther
     * @return array
     */
    public function getStats(bool $includeOther = false): array
    {
        $query = Venue::where('status', '1');

        if ($includeOther) {
            $dontKnowCount = $query->clone()->where('category_id', 6)->count();
            $otherCount = $query->clone()->where('category_id', 1)->count();
            $total = $dontKnowCount + $otherCount;
        } else {
            $dontKnowCount = $query->where('category_id', 6)->count();
            $otherCount = 0;
            $total = $dontKnowCount;
        }

        $withPlaceId = $query->clone()
            ->whereIn('category_id', $includeOther ? [1, 6] : [6])
            ->whereNotNull('g_place_id')
            ->where('g_place_id', '!=', '')
            ->count();

        $withoutPlaceId = $total - $withPlaceId;

        return [
            'total_needing_categorization' => $total,
            'dont_know_count' => $dontKnowCount,
            'other_count' => $otherCount,
            'with_place_id' => $withPlaceId,
            'without_place_id' => $withoutPlaceId,
            'processable' => $withPlaceId,
        ];
    }

    /**
     * Update a venue's name from Google Places (Google is authoritative).
     *
     * @param Venue $venue
     * @param string $newName
     * @return void
     */
    protected function updateVenueName(Venue $venue, string $newName): void
    {
        $oldName = $venue->name;

        DB::connection('squash_remote')->table('venues')
            ->where('id', $venue->id)
            ->update([
                'name' => $newName,
                'updated_at' => now(),
            ]);

        Log::info("Updated venue name from Google Places", [
            'venue_id' => $venue->id,
            'old_name' => $oldName,
            'new_name' => $newName,
        ]);

        // Update the venue object so subsequent operations use the new name
        $venue->name = $newName;
    }

    /**
     * Update a venue's Place ID in the database.
     *
     * @param Venue $venue
     * @param string $newPlaceId
     * @param string $source How the new Place ID was obtained
     * @return void
     */
    protected function updateVenuePlaceId(Venue $venue, string $newPlaceId, string $source): void
    {
        $oldPlaceId = $venue->g_place_id;

        // Check if the new Place ID already exists for another venue
        $existingVenue = DB::connection('squash_remote')->table('venues')
            ->where('g_place_id', $newPlaceId)
            ->where('id', '!=', $venue->id)
            ->first();

        if ($existingVenue) {
            Log::warning("Cannot update Place ID for venue #{$venue->id} - Place ID already exists for venue #{$existingVenue->id}", [
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'old_place_id' => $oldPlaceId,
                'new_place_id' => $newPlaceId,
                'existing_venue_id' => $existingVenue->id,
                'source' => $source,
            ]);
            
            // Don't update - this appears to be a duplicate venue
            // The venue will be flagged for manual review
            return;
        }

        try {
            DB::connection('squash_remote')->table('venues')
                ->where('id', $venue->id)
                ->update([
                    'g_place_id' => $newPlaceId,
                    'updated_at' => now(),
                ]);

            Log::info("Updated Place ID for venue #{$venue->id}", [
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'old_place_id' => $oldPlaceId,
                'new_place_id' => $newPlaceId,
                'source' => $source,
            ]);

            // Update the venue object so subsequent operations use the new Place ID
            $venue->g_place_id = $newPlaceId;
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation gracefully
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                Log::warning("Duplicate Place ID detected for venue #{$venue->id}", [
                    'venue_id' => $venue->id,
                    'venue_name' => $venue->name,
                    'old_place_id' => $oldPlaceId,
                    'new_place_id' => $newPlaceId,
                    'source' => $source,
                    'error' => $e->getMessage(),
                ]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Flag a venue for deletion when Place ID is expired and venue cannot be found.
     *
     * @param Venue $venue
     * @return void
     */
    protected function flagVenueForDeletion(Venue $venue): void
    {
        DB::connection('squash_remote')->table('venues')
            ->where('id', $venue->id)
            ->update([
                'status' => '3', // Flagged for Deletion
                'delete_reason_id' => 2, // "Venue is permanently closed"
                'reason_for_deletion' => 'Google Place ID expired, suggesting venue is closed',
                'deletion_request_by_user_id' => 1, // System/Itomic Webmaster
                'date_flagged_for_deletion' => now(),
                'updated_at' => now(),
            ]);

        Log::warning("Flagged venue #{$venue->id} for deletion - expired Place ID", [
            'venue_id' => $venue->id,
            'venue_name' => $venue->name,
            'old_place_id' => $venue->g_place_id,
        ]);
    }
}

