<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Facades\Storage;

class NewCategoryDetector
{
    protected array $unmappedVenues = [];
    protected array $newCategorySuggestions = [];

    /**
     * Track a venue with unmapped Google Places types.
     *
     * @param Venue $venue
     * @param array $googlePlacesData
     * @return void
     */
    public function trackUnmappedVenue(Venue $venue, array $googlePlacesData): void
    {
        $primaryType = $googlePlacesData['primaryType'] ?? 'unknown';
        $types = $googlePlacesData['types'] ?? [];

        if (!isset($this->unmappedVenues[$primaryType])) {
            $this->unmappedVenues[$primaryType] = [
                'count' => 0,
                'venues' => [],
                'all_types' => [],
            ];
        }

        $this->unmappedVenues[$primaryType]['count']++;
        $this->unmappedVenues[$primaryType]['venues'][] = [
            'id' => $venue->id,
            'name' => $venue->name,
            'address' => $venue->physical_address,
        ];
        
        // Merge all types seen for this primary type
        $this->unmappedVenues[$primaryType]['all_types'] = array_unique(
            array_merge($this->unmappedVenues[$primaryType]['all_types'], $types)
        );
    }

    /**
     * Track a new category suggestion from AI.
     *
     * @param Venue $venue
     * @param array $googlePlacesData
     * @param string|null $suggestedName
     * @return void
     */
    public function trackNewCategorySuggestion(Venue $venue, array $googlePlacesData, ?string $suggestedName): void
    {
        if (empty($suggestedName)) {
            return;
        }

        if (!isset($this->newCategorySuggestions[$suggestedName])) {
            $this->newCategorySuggestions[$suggestedName] = [
                'count' => 0,
                'venues' => [],
                'google_types' => [],
            ];
        }

        $this->newCategorySuggestions[$suggestedName]['count']++;
        $this->newCategorySuggestions[$suggestedName]['venues'][] = [
            'id' => $venue->id,
            'name' => $venue->name,
            'address' => $venue->physical_address,
        ];

        $primaryType = $googlePlacesData['primaryType'] ?? null;
        if ($primaryType) {
            $this->newCategorySuggestions[$suggestedName]['google_types'][] = $primaryType;
            $this->newCategorySuggestions[$suggestedName]['google_types'] = array_unique(
                $this->newCategorySuggestions[$suggestedName]['google_types']
            );
        }
    }

    /**
     * Generate and save a report of potential new categories.
     *
     * @return array
     */
    public function generateReport(): array
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'unmapped_types' => $this->processUnmappedTypes(),
            'ai_suggested_categories' => $this->processAISuggestions(),
        ];

        // Save to storage
        Storage::disk('local')->put(
            'logs/suggested-new-categories.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );

        return $report;
    }

    /**
     * Process unmapped types into a structured format.
     *
     * @return array
     */
    protected function processUnmappedTypes(): array
    {
        $processed = [];

        foreach ($this->unmappedVenues as $primaryType => $data) {
            // Only include if we have at least 3 venues with this type
            if ($data['count'] >= 3) {
                $processed[] = [
                    'primary_type' => $primaryType,
                    'venue_count' => $data['count'],
                    'related_types' => array_values($data['all_types']),
                    'sample_venues' => array_slice($data['venues'], 0, 5), // First 5 examples
                ];
            }
        }

        // Sort by venue count descending
        usort($processed, fn($a, $b) => $b['venue_count'] - $a['venue_count']);

        return $processed;
    }

    /**
     * Process AI suggestions into a structured format.
     *
     * @return array
     */
    protected function processAISuggestions(): array
    {
        $processed = [];

        foreach ($this->newCategorySuggestions as $categoryName => $data) {
            $processed[] = [
                'suggested_category_name' => $categoryName,
                'venue_count' => $data['count'],
                'google_types' => array_values($data['google_types']),
                'sample_venues' => array_slice($data['venues'], 0, 5), // First 5 examples
            ];
        }

        // Sort by venue count descending
        usort($processed, fn($a, $b) => $b['venue_count'] - $a['venue_count']);

        return $processed;
    }

    /**
     * Get summary of potential new categories.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'unmapped_type_groups' => count(array_filter(
                $this->unmappedVenues,
                fn($data) => $data['count'] >= 3
            )),
            'ai_suggested_categories' => count($this->newCategorySuggestions),
            'total_unmapped_venues' => array_sum(array_column($this->unmappedVenues, 'count')),
        ];
    }

    /**
     * Reset tracking data.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->unmappedVenues = [];
        $this->newCategorySuggestions = [];
    }
}

