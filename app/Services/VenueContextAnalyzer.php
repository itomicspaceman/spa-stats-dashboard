<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analyzes venue context to detect sub-venue relationships and parent facilities.
 * 
 * This helps distinguish between:
 * - Standalone dedicated squash facilities (should be "Dedicated facility")
 * - Squash clubs that are part of larger leisure centres (should be "Leisure centre")
 */
class VenueContextAnalyzer
{
    /**
     * Analyze if a venue appears to be a sub-venue of a larger facility.
     * 
     * @param Venue $venue The venue to analyze
     * @param array $googlePlacesData Google Places data including editorialSummary
     * @return array{is_sub_venue: bool, parent_facility_type: string|null, confidence: string, reasoning: string}
     */
    public function analyzeContext(Venue $venue, array $googlePlacesData): array
    {
        $result = [
            'is_sub_venue' => false,
            'parent_facility_type' => null,
            'confidence' => 'LOW',
            'reasoning' => '',
        ];

        $editorialSummary = $googlePlacesData['editorialSummary'] ?? '';
        $displayName = $googlePlacesData['displayName'] ?? '';
        $combinedText = strtolower($displayName . ' ' . $editorialSummary);

        // Method 1: Check editorial summary for parent facility indicators
        $parentIndicators = $this->checkEditorialSummaryForParent($combinedText);
        if ($parentIndicators['found']) {
            $result['is_sub_venue'] = true;
            $result['parent_facility_type'] = $parentIndicators['type'];
            $result['confidence'] = $parentIndicators['confidence'];
            $result['reasoning'] = $parentIndicators['reasoning'];
            return $result;
        }

        // Method 2: Check for co-located venues in our database
        $coLocated = $this->checkCoLocatedVenues($venue);
        if ($coLocated['found']) {
            $result['is_sub_venue'] = true;
            $result['parent_facility_type'] = $coLocated['type'];
            $result['confidence'] = $coLocated['confidence'];
            $result['reasoning'] = $coLocated['reasoning'];
            return $result;
        }

        // Method 3: Check name patterns for sub-venue indicators
        $namePattern = $this->checkNamePatternForSubVenue($displayName, $combinedText);
        if ($namePattern['found']) {
            $result['is_sub_venue'] = true;
            $result['parent_facility_type'] = $namePattern['type'];
            $result['confidence'] = $namePattern['confidence'];
            $result['reasoning'] = $namePattern['reasoning'];
            return $result;
        }

        return $result;
    }

    /**
     * Check editorial summary for mentions of parent facilities.
     * 
     * @param string $text Combined text from name and editorial summary
     * @return array{found: bool, type: string|null, confidence: string, reasoning: string}
     */
    protected function checkEditorialSummaryForParent(string $text): array
    {
        // High confidence indicators
        $highConfidencePatterns = [
            'part of' => 'leisure_centre',
            'located in' => 'leisure_centre',
            'within' => 'leisure_centre',
            'at the' => 'leisure_centre',
            'inside' => 'leisure_centre',
            'sports complex' => 'leisure_centre',
            'recreation center' => 'leisure_centre',
            'recreation centre' => 'leisure_centre',
            'leisure center' => 'leisure_centre',
            'leisure centre' => 'leisure_centre',
            'multi-sport' => 'leisure_centre',
            'multisport' => 'leisure_centre',
        ];

        foreach ($highConfidencePatterns as $pattern => $type) {
            if (stripos($text, $pattern) !== false) {
                return [
                    'found' => true,
                    'type' => $type,
                    'confidence' => 'HIGH',
                    'reasoning' => "Editorial summary mentions '{$pattern}' - indicates sub-venue of larger facility",
                ];
            }
        }

        // Medium confidence indicators
        $mediumConfidencePatterns = [
            'sports facility' => 'leisure_centre',
            'sports centre' => 'leisure_centre',
            'sports center' => 'leisure_centre',
            'fitness center' => 'gym',
            'fitness centre' => 'gym',
            'health club' => 'gym',
        ];

        foreach ($mediumConfidencePatterns as $pattern => $type) {
            if (stripos($text, $pattern) !== false) {
                return [
                    'found' => true,
                    'type' => $type,
                    'confidence' => 'MEDIUM',
                    'reasoning' => "Editorial summary mentions '{$pattern}' - may indicate sub-venue",
                ];
            }
        }

        return ['found' => false, 'type' => null, 'confidence' => 'LOW', 'reasoning' => ''];
    }

    /**
     * Check for co-located venues in our database.
     * 
     * @param Venue $venue The venue to check
     * @return array{found: bool, type: string|null, confidence: string, reasoning: string}
     */
    protected function checkCoLocatedVenues(Venue $venue): array
    {
        if (!$venue->latitude || !$venue->longitude) {
            return ['found' => false, 'type' => null, 'confidence' => 'LOW', 'reasoning' => ''];
        }

        // Check for venues at the same address (exact match)
        $sameAddress = DB::connection('squash_remote')
            ->table('venues')
            ->where('id', '!=', $venue->id)
            ->where('status', '1')
            ->where('physical_address', $venue->physical_address)
            ->where('suburb', $venue->suburb)
            ->where('state', $venue->state)
            ->whereNotNull('category_id')
            ->where('category_id', '!=', 6) // Not "Don't know"
            ->select('id', 'name', 'category_id')
            ->get();

        if ($sameAddress->count() > 0) {
            // Check if any co-located venue is a leisure centre or multi-sport facility
            $leisureCentreIds = [2]; // Leisure centre
            $multiSportIds = [2, 4]; // Leisure centre, Gym
            
            foreach ($sameAddress as $colocated) {
                if (in_array($colocated->category_id, $leisureCentreIds)) {
                    return [
                        'found' => true,
                        'type' => 'leisure_centre',
                        'confidence' => 'HIGH',
                        'reasoning' => "Co-located with '{$colocated->name}' (category: {$colocated->category_id}) at same address",
                    ];
                }
            }
        }

        // Check for venues very close by (within 100 meters)
        // Using Haversine formula approximation
        $lat = (float) $venue->latitude;
        $lng = (float) $venue->longitude;
        $radiusKm = 0.1; // 100 meters
        
        // Rough approximation: 1 degree latitude ≈ 111 km
        // 1 degree longitude ≈ 111 km * cos(latitude)
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

        $nearby = DB::connection('squash_remote')
            ->table('venues')
            ->where('id', '!=', $venue->id)
            ->where('status', '1')
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->whereNotNull('category_id')
            ->where('category_id', '!=', 6) // Not "Don't know"
            ->whereIn('category_id', [2, 4]) // Leisure centre or Gym
            ->select('id', 'name', 'category_id')
            ->limit(5)
            ->get();

        if ($nearby->count() > 0) {
            $nearest = $nearby->first();
            return [
                'found' => true,
                'type' => 'leisure_centre',
                'confidence' => 'MEDIUM',
                'reasoning' => "Very close to '{$nearest->name}' (category: {$nearest->category_id}) - may be part of same complex",
            ];
        }

        return ['found' => false, 'type' => null, 'confidence' => 'LOW', 'reasoning' => ''];
    }

    /**
     * Check name patterns that suggest sub-venue status.
     * 
     * @param string $displayName Venue name
     * @param string $combinedText Combined name and summary
     * @return array{found: bool, type: string|null, confidence: string, reasoning: string}
     */
    protected function checkNamePatternForSubVenue(string $displayName, string $combinedText): array
    {
        // First check: Exclude "squash centre/center" patterns - these are dedicated facilities
        $squashCentrePatterns = ['squash centre', 'squash center', 'squashcentre', 'squashcenter'];
        foreach ($squashCentrePatterns as $pattern) {
            if (stripos($combinedText, $pattern) !== false) {
                // This is a dedicated squash facility, not a sub-venue
                return ['found' => false, 'type' => null, 'confidence' => 'LOW', 'reasoning' => ''];
            }
        }
        
        // Pattern: "Parent Facility - Squash Club" or "Squash Club at Parent Facility"
        $subVenuePatterns = [
            '/^(.+?)\s*[-–—]\s*(squash|racquet)/i' => 'leisure_centre', // "Sports Complex - Squash Club"
            '/(squash|racquet).*?\s+(at|in|within)\s+(.+)/i' => 'leisure_centre', // "Squash Club at Sports Centre"
        ];

        foreach ($subVenuePatterns as $pattern => $type) {
            if (preg_match($pattern, $displayName, $matches)) {
                return [
                    'found' => true,
                    'type' => $type,
                    'confidence' => 'MEDIUM',
                    'reasoning' => "Name pattern suggests sub-venue: '{$displayName}'",
                ];
            }
        }

        // Check if name contains both squash and multi-sport indicators
        // BUT exclude "squash centre/center" which is a common name for dedicated squash facilities
        $hasSquash = preg_match('/squash/i', $displayName);
        
        // Exclude "squash centre/center" patterns - these are dedicated facilities, not multi-sport
        $squashCentrePatterns = ['squash centre', 'squash center', 'squashcentre', 'squashcenter'];
        $isSquashCentre = false;
        foreach ($squashCentrePatterns as $pattern) {
            if (stripos($displayName, $pattern) !== false) {
                $isSquashCentre = true;
                break;
            }
        }
        
        // If it's a "squash centre", don't treat it as multi-sport
        if ($isSquashCentre) {
            return ['found' => false, 'type' => null, 'confidence' => 'LOW', 'reasoning' => ''];
        }
        
        // Check for multi-sport indicators (excluding "centre/center" when part of "squash centre")
        $multiSportIndicators = ['sports', 'recreation', 'leisure', 'fitness', 'multi', 'multisport', 'complex'];
        $hasMultiSport = false;
        
        foreach ($multiSportIndicators as $indicator) {
            if (stripos($displayName, $indicator) !== false) {
                $hasMultiSport = true;
                break;
            }
        }
        
        // Also check for "centre/center" but only if NOT part of "squash centre"
        if (!$isSquashCentre) {
            if (stripos($displayName, 'centre') !== false || stripos($displayName, 'center') !== false) {
                // Check if it's a multi-sport centre (e.g., "Sports Centre", "Recreation Centre")
                $multiSportCentrePatterns = ['sports centre', 'sports center', 'recreation centre', 'recreation center', 
                                            'leisure centre', 'leisure center', 'fitness centre', 'fitness center'];
                foreach ($multiSportCentrePatterns as $pattern) {
                    if (stripos($displayName, $pattern) !== false) {
                        $hasMultiSport = true;
                        break;
                    }
                }
            }
        }

        if ($hasSquash && $hasMultiSport) {
            return [
                'found' => true,
                'type' => 'leisure_centre',
                'confidence' => 'MEDIUM',
                'reasoning' => "Name contains both squash and multi-sport indicators",
            ];
        }

        return ['found' => false, 'type' => null, 'confidence' => 'LOW', 'reasoning' => ''];
    }

    /**
     * Adjust category recommendation based on context analysis.
     * 
     * @param int|null $recommendedCategoryId Original recommended category
     * @param array $contextAnalysis Result from analyzeContext()
     * @return array{category_id: int|null, confidence: string, reasoning: string, adjusted: bool}
     */
    public function adjustCategoryForContext(?int $recommendedCategoryId, array $contextAnalysis): array
    {
        // If not a sub-venue, return original recommendation
        if (!$contextAnalysis['is_sub_venue']) {
            return [
                'category_id' => $recommendedCategoryId,
                'confidence' => 'HIGH',
                'reasoning' => 'No sub-venue indicators found',
                'adjusted' => false,
            ];
        }

        // If recommended category is "Dedicated facility" (ID 5) but it's a sub-venue,
        // adjust to "Leisure centre" (ID 2) if parent is leisure centre
        if ($recommendedCategoryId === 5 && $contextAnalysis['parent_facility_type'] === 'leisure_centre') {
            return [
                'category_id' => 2, // Leisure centre
                'confidence' => $contextAnalysis['confidence'],
                'reasoning' => "Originally 'Dedicated facility', but context indicates sub-venue of larger facility. " . $contextAnalysis['reasoning'],
                'adjusted' => true,
            ];
        }

        // If recommended category is "Dedicated facility" but it's a sub-venue of a gym,
        // adjust to "Gym or health & fitness centre" (ID 4)
        if ($recommendedCategoryId === 5 && $contextAnalysis['parent_facility_type'] === 'gym') {
            return [
                'category_id' => 4, // Gym or health & fitness centre
                'confidence' => $contextAnalysis['confidence'],
                'reasoning' => "Originally 'Dedicated facility', but context indicates sub-venue of gym/fitness facility. " . $contextAnalysis['reasoning'],
                'adjusted' => true,
            ];
        }

        // For other cases, keep original but lower confidence
        return [
            'category_id' => $recommendedCategoryId,
            'confidence' => $contextAnalysis['confidence'] === 'HIGH' ? 'MEDIUM' : 'LOW',
            'reasoning' => "Sub-venue detected but keeping original category. " . $contextAnalysis['reasoning'],
            'adjusted' => false,
        ];
    }
}

