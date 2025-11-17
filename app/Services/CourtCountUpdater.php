<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourtCountUpdater
{
    /**
     * Update a venue's court count with audit logging.
     *
     * @param int $venueId
     * @param int $newCourtCount
     * @param array $metadata Analysis metadata (confidence, reasoning, source, etc.)
     * @return array{success: bool, message: string, venue_id: int, old_court_count: int|null, new_court_count: int}
     */
    public function updateVenue(int $venueId, int $newCourtCount, array $metadata): array
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
                    'old_court_count' => null,
                    'new_court_count' => $newCourtCount,
                ];
            }

            $oldCourtCount = $venue->no_of_courts ?? 0;

            // Create audit log entry
            $this->createAuditLog($venueId, $oldCourtCount, $newCourtCount, $metadata);

            // Update venue court counts
            // Update both no_of_courts and no_of_singles_courts as per requirements
            DB::connection('squash_remote')
                ->table('venues')
                ->where('id', $venueId)
                ->update([
                    'no_of_courts' => $newCourtCount,
                    'no_of_singles_courts' => $newCourtCount, // As per requirements
                    'updated_at' => now(),
                ]);

            DB::connection('squash_remote')->commit();

            Log::info('Venue court count updated', [
                'venue_id' => $venueId,
                'old_court_count' => $oldCourtCount,
                'new_court_count' => $newCourtCount,
                'confidence' => $metadata['confidence'] ?? 'UNKNOWN',
                'source_type' => $metadata['source_type'] ?? 'UNKNOWN',
            ]);

            return [
                'success' => true,
                'message' => 'Court count updated successfully',
                'venue_id' => $venueId,
                'old_court_count' => $oldCourtCount,
                'new_court_count' => $newCourtCount,
            ];

        } catch (\Exception $e) {
            DB::connection('squash_remote')->rollBack();
            
            Log::error('Failed to update venue court count', [
                'venue_id' => $venueId,
                'new_court_count' => $newCourtCount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'venue_id' => $venueId,
                'old_court_count' => null,
                'new_court_count' => $newCourtCount,
            ];
        }
    }

    /**
     * Flag a venue for deletion when no evidence of squash courts is found.
     *
     * @param int $venueId
     * @param string $reasoning
     * @param array $additionalDetails Optional additional context (source_url, search_api_used, etc.)
     * @return array{success: bool, message: string}
     */
    public function flagVenueForDeletion(int $venueId, string $reasoning, array $additionalDetails = []): array
    {
        try {
            DB::connection('squash_remote')->beginTransaction();

            $venue = Venue::find($venueId);
            
            if (!$venue) {
                DB::connection('squash_remote')->rollBack();
                return [
                    'success' => false,
                    'message' => 'Venue not found',
                ];
            }

            // Build detailed deletion reason with supporting evidence
            $detailedReason = $this->buildDetailedDeletionReason($venue, $reasoning, $additionalDetails);

            // Build supporting evidence for more_details field
            $moreDetails = $this->buildMoreDetails($venue, $reasoning, $additionalDetails);

            // Flag venue for deletion
            DB::connection('squash_remote')
                ->table('venues')
                ->where('id', $venueId)
                ->update([
                    'status' => '3', // Flagged for Deletion
                    'delete_reason_id' => 3, // "No evidence of squash courts found"
                    'reason_for_deletion' => $detailedReason,
                    'more_details' => $moreDetails, // Supporting text evidence
                    'deletion_request_by_user_id' => 1, // System/Itomic Webmaster
                    'date_flagged_for_deletion' => now(),
                    'updated_at' => now(),
                ]);

            DB::connection('squash_remote')->commit();

            Log::warning("Flagged venue #{$venueId} for deletion - no evidence of squash courts", [
                'venue_id' => $venueId,
                'venue_name' => $venue->name,
                'reasoning' => $reasoning,
            ]);

            return [
                'success' => true,
                'message' => 'Venue flagged for deletion',
            ];

        } catch (\Exception $e) {
            DB::connection('squash_remote')->rollBack();
            
            Log::error('Failed to flag venue for deletion', [
                'venue_id' => $venueId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build a detailed deletion reason with supporting evidence.
     *
     * @param Venue $venue
     * @param string $reasoning
     * @param array $additionalDetails
     * @return string
     */
    protected function buildDetailedDeletionReason(Venue $venue, string $reasoning, array $additionalDetails): string
    {
        $details = [];
        
        // Main reasoning
        $details[] = "Reason: {$reasoning}";
        
        // Venue information
        $details[] = "Venue: {$venue->name}";
        if ($venue->physical_address) {
            $details[] = "Address: {$venue->physical_address}";
        }
        
        // Search details if available
        if (!empty($additionalDetails['source_url'])) {
            $details[] = "Searched URL: {$additionalDetails['source_url']}";
        }
        if (!empty($additionalDetails['search_api_used'])) {
            $details[] = "Search method: {$additionalDetails['search_api_used']}";
        }
        if (!empty($additionalDetails['search_results']) && is_array($additionalDetails['search_results'])) {
            $resultCount = count($additionalDetails['search_results']);
            $details[] = "Search results checked: {$resultCount}";
        }
        
        // Additional context
        if (!empty($additionalDetails['venue_website'])) {
            $details[] = "Venue website checked: {$additionalDetails['venue_website']}";
        }
        
        $details[] = "Flagged by: Automated AI Court Count System";
        $details[] = "Date: " . now()->format('Y-m-d H:i:s');
        
        return implode("\n", $details);
    }

    /**
     * Build supporting evidence text for the more_details field.
     *
     * @param Venue $venue
     * @param string $reasoning
     * @param array $additionalDetails
     * @return string
     */
    protected function buildMoreDetails(Venue $venue, string $reasoning, array $additionalDetails): string
    {
        $details = [];
        
        // Brief summary
        $details[] = "Automated web search found no evidence of squash courts at this venue.";
        
        // Search evidence
        if (!empty($additionalDetails['search_api_used'])) {
            $details[] = "Search method: {$additionalDetails['search_api_used']}";
        }
        
        if (!empty($additionalDetails['venue_website'])) {
            $details[] = "Venue website checked: {$additionalDetails['venue_website']}";
        }
        
        if (!empty($additionalDetails['search_results']) && is_array($additionalDetails['search_results'])) {
            $resultCount = count($additionalDetails['search_results']);
            $details[] = "Checked {$resultCount} search result(s)";
            
            // Include first few relevant URLs if available
            $urls = [];
            foreach (array_slice($additionalDetails['search_results'], 0, 3) as $result) {
                if (!empty($result['url'])) {
                    $urls[] = $result['url'];
                }
            }
            if (!empty($urls)) {
                $details[] = "Sources checked: " . implode(", ", $urls);
            }
        }
        
        // AI reasoning if available
        if (!empty($reasoning) && strlen($reasoning) < 500) {
            $details[] = "AI analysis: {$reasoning}";
        }
        
        return implode(" ", $details);
    }

    /**
     * Create an audit log entry for the court count update.
     *
     * @param int $venueId
     * @param int|null $oldCourtCount
     * @param int $newCourtCount
     * @param array $metadata
     * @return void
     */
    protected function createAuditLog(int $venueId, ?int $oldCourtCount, int $newCourtCount, array $metadata): void
    {
        // Prepare search results summary
        $searchResultsSummary = null;
        if (isset($metadata['search_results'])) {
            $searchResultsSummary = json_encode([
                'total_results' => count($metadata['search_results']),
                'results' => array_map(function ($result) {
                    return [
                        'title' => $result['title'] ?? '',
                        'url' => $result['url'] ?? '',
                        'source_type' => $result['source_type'] ?? 'OTHER',
                    ];
                }, array_slice($metadata['search_results'] ?? [], 0, 5)), // Store first 5 results
            ]);
        }

        // Log to our custom venue_court_count_updates table
        DB::connection('squash_remote')->table('venue_court_count_updates')->insert([
            'venue_id' => $venueId,
            'old_court_count' => $oldCourtCount,
            'new_court_count' => $newCourtCount,
            'confidence_level' => $metadata['confidence'] ?? 'LOW',
            'reasoning' => $metadata['reasoning'] ?? 'No reasoning provided',
            'source_url' => $metadata['source_url'] ?? null,
            'source_type' => $metadata['source_type'] ?? null,
            'search_api_used' => $metadata['search_api_used'] ?? null,
            'search_results_summary' => $searchResultsSummary,
            'created_at' => now(),
            'created_by' => 'Automated: AI Court Count System',
        ]);
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
            ->table('venue_court_count_updates')
            ->where('venue_id', $venueId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get statistics about court count updates.
     *
     * @param string|null $since Optional date to filter from (e.g., '2025-01-01')
     * @return array
     */
    public function getUpdateStats(?string $since = null): array
    {
        $query = DB::connection('squash_remote')
            ->table('venue_court_count_updates');

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

        $bySourceType = $query->clone()
            ->select('source_type', DB::raw('COUNT(*) as count'))
            ->groupBy('source_type')
            ->get()
            ->pluck('count', 'source_type')
            ->toArray();

        $bySearchApi = $query->clone()
            ->select('search_api_used', DB::raw('COUNT(*) as count'))
            ->whereNotNull('search_api_used')
            ->groupBy('search_api_used')
            ->get()
            ->pluck('count', 'search_api_used')
            ->toArray();

        return [
            'total_updates' => $total,
            'by_confidence' => $byConfidence,
            'by_source_type' => $bySourceType,
            'by_search_api' => $bySearchApi,
        ];
    }
}

