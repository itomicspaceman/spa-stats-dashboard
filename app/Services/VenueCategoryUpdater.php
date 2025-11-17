<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VenueCategoryUpdater
{
    /**
     * Update a venue's category with audit logging.
     *
     * @param int $venueId
     * @param int $newCategoryId
     * @param array $metadata Categorization metadata (Google types, confidence, reasoning, etc.)
     * @return array{success: bool, message: string, venue_id: int, old_category_id: int|null, new_category_id: int}
     */
    public function updateVenue(int $venueId, int $newCategoryId, array $metadata): array
    {
        try {
            DB::connection('squash_remote')->beginTransaction();

            // Get current venue
            $venue = Venue::find($venueId);
            
            if (!$venue) {
                DB::connection('squash_remote')->rollBack();
                return [
                    'success' => false,
                    'message' => 'Venue not found',
                    'venue_id' => $venueId,
                    'old_category_id' => null,
                    'new_category_id' => $newCategoryId,
                ];
            }

            $oldCategoryId = $venue->category_id;

            // Create audit log entry
            $this->createAuditLog($venueId, $oldCategoryId, $newCategoryId, $metadata);

            // Update venue category
            DB::connection('squash_remote')
                ->table('venues')
                ->where('id', $venueId)
                ->update([
                    'category_id' => $newCategoryId,
                    'updated_at' => now(),
                ]);

            DB::connection('squash_remote')->commit();

            Log::info('Venue category updated', [
                'venue_id' => $venueId,
                'old_category_id' => $oldCategoryId,
                'new_category_id' => $newCategoryId,
                'confidence' => $metadata['confidence'] ?? 'UNKNOWN',
                'source' => $metadata['source'] ?? 'UNKNOWN',
            ]);

            return [
                'success' => true,
                'message' => 'Category updated successfully',
                'venue_id' => $venueId,
                'old_category_id' => $oldCategoryId,
                'new_category_id' => $newCategoryId,
            ];

        } catch (\Exception $e) {
            DB::connection('squash_remote')->rollBack();
            
            Log::error('Failed to update venue category', [
                'venue_id' => $venueId,
                'new_category_id' => $newCategoryId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'venue_id' => $venueId,
                'old_category_id' => null,
                'new_category_id' => $newCategoryId,
            ];
        }
    }

    /**
     * Create an audit log entry for the category update.
     *
     * @param int $venueId
     * @param int|null $oldCategoryId
     * @param int $newCategoryId
     * @param array $metadata
     * @return void
     */
    protected function createAuditLog(int $venueId, ?int $oldCategoryId, int $newCategoryId, array $metadata): void
    {
        $googlePlaceTypes = null;
        if (isset($metadata['google_places_data']['types'])) {
            $googlePlaceTypes = json_encode([
                'primary_type' => $metadata['google_places_data']['primaryType'] ?? null,
                'types' => $metadata['google_places_data']['types'] ?? [],
                'matched_type' => $metadata['matched_type'] ?? null,
            ]);
        }

        // Log to our custom venue_category_updates table
        DB::connection('squash_remote')->table('venue_category_updates')->insert([
            'venue_id' => $venueId,
            'old_category_id' => $oldCategoryId,
            'new_category_id' => $newCategoryId,
            'google_place_types' => $googlePlaceTypes,
            'confidence_level' => $metadata['confidence'] ?? 'LOW',
            'reasoning' => $metadata['reasoning'] ?? 'No reasoning provided',
            'source' => $metadata['source'] ?? 'MANUAL',
            'created_at' => now(),
            'created_by' => 'Automated: AI Categorization System',
        ]);
    }

    /**
     * Get category name by ID.
     *
     * @param int|null $categoryId
     * @return string
     */
    protected function getCategoryName(?int $categoryId): string
    {
        if ($categoryId === null) {
            return 'Unknown';
        }

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

    /**
     * Batch update multiple venues.
     *
     * @param array $updates Array of ['venue_id' => int, 'category_id' => int, 'metadata' => array]
     * @return array{success: int, failed: int, results: array}
     */
    public function batchUpdate(array $updates): array
    {
        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($updates as $update) {
            $result = $this->updateVenue(
                $update['venue_id'],
                $update['category_id'],
                $update['metadata'] ?? []
            );

            if ($result['success']) {
                $successCount++;
            } else {
                $failedCount++;
            }

            $results[] = $result;
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * Get audit log entries for a venue.
     *
     * @param int $venueId
     * @return array
     */
    public function getAuditLog(int $venueId): array
    {
        return DB::connection('squash_remote')
            ->table('venue_category_updates')
            ->where('venue_id', $venueId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get statistics about category updates.
     *
     * @param string|null $since Optional date to filter from (e.g., '2025-01-01')
     * @return array
     */
    public function getUpdateStats(?string $since = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venue_category_updates');

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $total = $query->count();
        
        $byConfidence = $query->clone()
            ->select('confidence_level', DB::raw('COUNT(*) as count'))
            ->groupBy('confidence_level')
            ->get()
            ->pluck('count', 'confidence_level')
            ->toArray();

        $bySource = $query->clone()
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->get()
            ->pluck('count', 'source')
            ->toArray();

        return [
            'total_updates' => $total,
            'by_confidence' => $byConfidence,
            'by_source' => $bySource,
        ];
    }
}

