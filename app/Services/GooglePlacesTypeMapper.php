<?php

namespace App\Services;

class GooglePlacesTypeMapper
{
    /**
     * Confidence levels for category mappings.
     */
    const CONFIDENCE_HIGH = 'HIGH';
    const CONFIDENCE_MEDIUM = 'MEDIUM';
    const CONFIDENCE_LOW = 'LOW';

    protected ?GoogleTranslateService $translateService;

    public function __construct(?GoogleTranslateService $translateService = null)
    {
        $this->translateService = $translateService;
    }

    /**
     * Set the translate service (for dependency injection).
     *
     * @param GoogleTranslateService $translateService
     * @return void
     */
    public function setTranslateService(GoogleTranslateService $translateService): void
    {
        $this->translateService = $translateService;
    }

    /**
     * Mapping from Google Places types to venue category IDs.
     * Based on: https://developers.google.com/maps/documentation/places/web-service/place-types
     *
     * IMPORTANT: "Dedicated facility" (ID 5) means dedicated to SQUASH ONLY.
     * General sports facilities should map to "Leisure centre" (ID 2) instead.
     *
     * @var array<string, array{category_id: int, confidence: string}>
     */
    protected array $typeMapping = [
        // Gym or health & fitness centre (ID 4)
        'gym' => ['category_id' => 4, 'confidence' => self::CONFIDENCE_HIGH],
        'health_club' => ['category_id' => 4, 'confidence' => self::CONFIDENCE_HIGH],
        'fitness_center' => ['category_id' => 4, 'confidence' => self::CONFIDENCE_HIGH],
        
        // Dedicated facility (ID 5) - ONLY for squash-specific venues
        // Note: Google Places doesn't have specific "squash_club" or "squash_court" types
        // These venues will likely need AI evaluation to determine if they're squash-only
        // Removed: sports_complex, sports_club, stadium, athletic_field
        // (These are too general and likely multi-sport facilities)
        
        // Hotel or resort (ID 7)
        'hotel' => ['category_id' => 7, 'confidence' => self::CONFIDENCE_HIGH],
        'resort_hotel' => ['category_id' => 7, 'confidence' => self::CONFIDENCE_HIGH],
        'lodging' => ['category_id' => 7, 'confidence' => self::CONFIDENCE_MEDIUM],
        
        // School (ID 3)
        'school' => ['category_id' => 3, 'confidence' => self::CONFIDENCE_HIGH],
        'primary_school' => ['category_id' => 3, 'confidence' => self::CONFIDENCE_HIGH],
        'secondary_school' => ['category_id' => 3, 'confidence' => self::CONFIDENCE_HIGH],
        'high_school' => ['category_id' => 3, 'confidence' => self::CONFIDENCE_HIGH],
        'middle_school' => ['category_id' => 3, 'confidence' => self::CONFIDENCE_HIGH],
        'preschool' => ['category_id' => 3, 'confidence' => self::CONFIDENCE_HIGH],
        
        // College or university (ID 8)
        'university' => ['category_id' => 8, 'confidence' => self::CONFIDENCE_HIGH],
        'college' => ['category_id' => 8, 'confidence' => self::CONFIDENCE_HIGH],
        
        // Community hall (ID 11)
        'community_center' => ['category_id' => 11, 'confidence' => self::CONFIDENCE_HIGH],
        
        // Leisure centre (ID 2) - Multi-sport/recreation facilities
        'recreation_center' => ['category_id' => 2, 'confidence' => self::CONFIDENCE_HIGH],
        'aquatic_center' => ['category_id' => 2, 'confidence' => self::CONFIDENCE_MEDIUM],
        'sports_complex' => ['category_id' => 2, 'confidence' => self::CONFIDENCE_MEDIUM],
        'sports_club' => ['category_id' => 2, 'confidence' => self::CONFIDENCE_MEDIUM],
        'athletic_field' => ['category_id' => 2, 'confidence' => self::CONFIDENCE_LOW],
        'stadium' => ['category_id' => 2, 'confidence' => self::CONFIDENCE_LOW],
        
        // Shopping centre (ID 10)
        'shopping_mall' => ['category_id' => 10, 'confidence' => self::CONFIDENCE_HIGH],
        
        // Private club (ID 14)
        'private_club' => ['category_id' => 14, 'confidence' => self::CONFIDENCE_HIGH],
        
        // Country club (ID 15)
        'country_club' => ['category_id' => 15, 'confidence' => self::CONFIDENCE_HIGH],
        'golf_club' => ['category_id' => 15, 'confidence' => self::CONFIDENCE_MEDIUM],
        
        // Military (ID 9)
        'military_base' => ['category_id' => 9, 'confidence' => self::CONFIDENCE_HIGH],
        
        // Business complex (ID 13)
        'office_building' => ['category_id' => 13, 'confidence' => self::CONFIDENCE_MEDIUM],
        'corporate_office' => ['category_id' => 13, 'confidence' => self::CONFIDENCE_MEDIUM],
        
        // Private residence (ID 12)
        'private_residence' => ['category_id' => 12, 'confidence' => self::CONFIDENCE_HIGH],
        'residential' => ['category_id' => 12, 'confidence' => self::CONFIDENCE_MEDIUM],
    ];

    /**
     * Map Google Places types to a venue category.
     *
     * @param array $googlePlacesData Data from Google Places API including types and primaryType
     * @return array{category_id: int|null, confidence: string, reasoning: string, matched_type: string|null}
     */
    public function mapToCategory(array $googlePlacesData): array
    {
        $primaryType = $googlePlacesData['primaryType'] ?? null;
        $types = $googlePlacesData['types'] ?? [];
        $displayName = $googlePlacesData['displayName'] ?? '';
        $editorialSummary = $googlePlacesData['editorialSummary'] ?? '';
        
        // Check venue name/description for category indicators (HIGHEST PRIORITY)
        // This works for ALL categories, not just squash
        $nameResult = $this->checkVenueNameForCategory($displayName, $editorialSummary);
        if ($nameResult) {
            return $nameResult;
        }
        
        // Check for combination patterns (these override simple mappings)
        $combinationResult = $this->checkCombinationPatterns($types, $primaryType);
        if ($combinationResult) {
            return $combinationResult;
        }
        
        // Try primary type mapping
        if ($primaryType && isset($this->typeMapping[$primaryType])) {
            $mapping = $this->typeMapping[$primaryType];
            return [
                'category_id' => $mapping['category_id'],
                'confidence' => $mapping['confidence'],
                'reasoning' => "Matched primary type: {$primaryType}",
                'matched_type' => $primaryType,
            ];
        }
        
        // Try secondary types (downgrade confidence)
        foreach ($types as $type) {
            if (isset($this->typeMapping[$type])) {
                $mapping = $this->typeMapping[$type];
                // Downgrade confidence if not from primary type
                $confidence = $mapping['confidence'] === self::CONFIDENCE_HIGH 
                    ? self::CONFIDENCE_MEDIUM 
                    : self::CONFIDENCE_LOW;
                
                return [
                    'category_id' => $mapping['category_id'],
                    'confidence' => $confidence,
                    'reasoning' => "Matched secondary type: {$type}",
                    'matched_type' => $type,
                ];
            }
        }
        
        // No mapping found - try translation fallback if name appears non-English
        if ($this->translateService && $this->translateService->isConfigured()) {
            $translatedResult = $this->tryTranslationFallback($displayName, $editorialSummary);
            if ($translatedResult) {
                return $translatedResult;
            }
        }
        
        // No mapping found
        return [
            'category_id' => null,
            'confidence' => self::CONFIDENCE_LOW,
            'reasoning' => 'No matching Google Places type found in mapping table',
            'matched_type' => null,
        ];
    }

    /**
     * Try translation fallback for non-English venue names.
     * Only used when initial name analysis fails.
     *
     * @param string $displayName Venue name
     * @param string $editorialSummary Editorial summary
     * @return array|null Returns mapping array if translation helps, null otherwise
     */
    protected function tryTranslationFallback(string $displayName, string $editorialSummary): ?array
    {
        // Only translate if text appears non-English
        if (!$this->translateService->appearsNonEnglish($displayName)) {
            return null; // Already English or no translation needed
        }

        // Translate displayName to English
        $translatedName = $this->translateService->translateToEnglish($displayName);
        
        if (!$translatedName) {
            return null; // Translation failed
        }

        // Try name analysis on translated text
        $translatedResult = $this->checkVenueNameForCategory($translatedName, '');
        
        if ($translatedResult) {
            // Update reasoning to indicate translation was used
            $translatedResult['reasoning'] = 'Translated from original language: ' . $translatedResult['reasoning'];
            $translatedResult['matched_type'] = 'translated_' . ($translatedResult['matched_type'] ?? 'name');
            
            return $translatedResult;
        }

        return null; // Translation didn't help
    }

    /**
     * Check venue name and description for category indicators.
     * This is checked FIRST as venue names are highly reliable indicators.
     * Supports multilingual venue names.
     *
     * @param string $displayName Venue name from Google Places
     * @param string $editorialSummary Editorial summary from Google Places
     * @return array|null Returns mapping array or null if no clear match
     */
    protected function checkVenueNameForCategory(string $displayName, string $editorialSummary): ?array
    {
        $combinedText = strtolower($displayName . ' ' . $editorialSummary);
        
        // Category name patterns (multilingual)
        // Format: [category_id => [patterns]]
        $categoryPatterns = [
            // Dedicated facility (ID 5) - Squash-only
            5 => [
                'high' => ['squash club', 'squash centre', 'squash center', 'squash court', 'squash courts', 
                          'squash facility', 'club de squash', 'club squash', 'centro de squash',
                          'squashclub', 'squashcentre', 'squashcenter'],
                'medium' => ['squash'],
            ],
            
            // Leisure centre (ID 2)
            2 => [
                'high' => ['leisure centre', 'leisure center', 'recreation centre', 'recreation center',
                          'centro recreativo', 'centre de loisirs', 'freizeitzentrum', 'sports centre',
                          'sports center', 'centro deportivo', 'multi-sport', 'multisport'],
                'medium' => ['leisure', 'recreation'],
            ],
            
            // School (ID 3)
            3 => [
                'high' => ['school', 'escuela', 'école', 'schule', 'primary school', 'secondary school',
                          'high school', 'middle school', 'elementary', 'colegio'],
                'medium' => [],
            ],
            
            // Gym or health & fitness centre (ID 4)
            4 => [
                'high' => ['gym', 'fitness centre', 'fitness center', 'health club', 'gimnasio',
                          'salle de sport', 'fitnesscenter', 'health & fitness'],
                'medium' => ['fitness'],
            ],
            
            // Hotel or resort (ID 7)
            7 => [
                'high' => ['hotel', 'resort', 'inn', 'lodge'],
                'medium' => [],
            ],
            
            // College or university (ID 8)
            8 => [
                'high' => ['university', 'universidad', 'université', 'universität', 'college',
                          'colegio universitario', 'campus'],
                'medium' => [],
            ],
            
            // Military (ID 9)
            9 => [
                'high' => ['military', 'army', 'navy', 'air force', 'base militar', 'base militaire',
                          'militärbasis', 'barracks', 'cuartel'],
                'medium' => [],
            ],
            
            // Shopping centre (ID 10)
            10 => [
                'high' => ['shopping centre', 'shopping center', 'shopping mall', 'mall',
                          'centro comercial', 'centre commercial', 'einkaufszentrum'],
                'medium' => [],
            ],
            
            // Community hall (ID 11)
            11 => [
                'high' => ['community centre', 'community center', 'community hall', 'civic centre',
                          'centro comunitario', 'centre communautaire', 'gemeindezentrum'],
                'medium' => ['community'],
            ],
            
            // Private club (ID 14)
            14 => [
                'high' => ['private club', 'members club', 'club privado', 'club privé'],
                'medium' => [],
            ],
            
            // Country club (ID 15)
            15 => [
                'high' => ['country club', 'golf club', 'club de golf', 'club de campo'],
                'medium' => [],
            ],
        ];
        
        // Check each category's patterns
        foreach ($categoryPatterns as $categoryId => $confidenceLevels) {
            // Check HIGH confidence patterns first
            foreach ($confidenceLevels['high'] as $pattern) {
                if (stripos($combinedText, $pattern) !== false) {
                    // Special handling for squash (check for multi-sport)
                    if ($categoryId === 5) {
                        $multiSportIndicators = ['tennis', 'badminton', 'racquetball', 'swimming', 'gym', 
                                                'fitness', 'multi-sport', 'recreation', 'leisure centre'];
                        $hasMultiSport = false;
                        foreach ($multiSportIndicators as $indicator) {
                            if (stripos($combinedText, $indicator) !== false) {
                                $hasMultiSport = true;
                                break;
                            }
                        }
                        
                        if ($hasMultiSport) {
                            // Multi-sport facility, not squash-only
                            return [
                                'category_id' => 2, // Leisure centre
                                'confidence' => self::CONFIDENCE_HIGH,
                                'reasoning' => "Venue name mentions squash but also other sports - indicates multi-sport leisure centre",
                                'matched_type' => 'name_multi_sport',
                            ];
                        }
                    }
                    
                    return [
                        'category_id' => $categoryId,
                        'confidence' => self::CONFIDENCE_HIGH,
                        'reasoning' => "Venue name contains '{$pattern}' - strong category indicator",
                        'matched_type' => 'name_high_confidence',
                    ];
                }
            }
            
            // Check MEDIUM confidence patterns
            foreach ($confidenceLevels['medium'] as $pattern) {
                if (stripos($combinedText, $pattern) !== false) {
                    // For squash, check multi-sport indicators
                    if ($categoryId === 5) {
                        $multiSportIndicators = ['tennis', 'badminton', 'swimming', 'gym', 'fitness', 'recreation', 'leisure'];
                        $hasMultiSport = false;
                        foreach ($multiSportIndicators as $indicator) {
                            if (stripos($combinedText, $indicator) !== false) {
                                $hasMultiSport = true;
                                break;
                            }
                        }
                        
                        if ($hasMultiSport) {
                            continue; // Skip this, not squash-only
                        }
                    }
                    
                    return [
                        'category_id' => $categoryId,
                        'confidence' => self::CONFIDENCE_MEDIUM,
                        'reasoning' => "Venue name contains '{$pattern}' - likely category indicator",
                        'matched_type' => 'name_medium_confidence',
                    ];
                }
            }
        }
        
        return null; // No clear name-based match
    }

    /**
     * Check for combination patterns that override simple type mappings.
     *
     * @param array $types All Google Places types
     * @param string|null $primaryType Primary type
     * @return array|null Returns mapping array or null if no pattern matches
     */
    protected function checkCombinationPatterns(array $types, ?string $primaryType): ?array
    {
        // Gym + Swimming Pool = Leisure centre (not just Gym)
        // If there's a pool, it's definitely a leisure centre, not just a gym
        if (in_array('gym', $types) && 
            (in_array('swimming_pool', $types) || in_array('aquatic_center', $types))) {
            return [
                'category_id' => 2, // Leisure centre
                'confidence' => self::CONFIDENCE_HIGH,
                'reasoning' => 'Gym with swimming pool indicates multi-facility leisure centre',
                'matched_type' => 'gym+swimming_pool',
            ];
        }

        // Gym + Multiple sports facilities = Leisure centre
        $sportsIndicators = ['sports_complex', 'athletic_field', 'stadium', 'ice_skating_rink'];
        $hasSportsIndicators = count(array_intersect($types, $sportsIndicators)) > 0;
        
        if (in_array('gym', $types) && $hasSportsIndicators) {
            return [
                'category_id' => 2, // Leisure centre
                'confidence' => self::CONFIDENCE_MEDIUM,
                'reasoning' => 'Gym with multiple sports facilities indicates leisure centre',
                'matched_type' => 'gym+sports_facilities',
            ];
        }

        // Country club patterns (golf-related or resort-like, less sports-focused)
        if (in_array('country_club', $types) || 
            (in_array('golf_club', $types) && (in_array('restaurant', $types) || in_array('lodging', $types)))) {
            return [
                'category_id' => 15, // Country club
                'confidence' => self::CONFIDENCE_HIGH,
                'reasoning' => 'Golf club with social/dining facilities indicates country club',
                'matched_type' => 'country_club',
            ];
        }

        // Private club (sports-focused, no golf)
        if (in_array('private_club', $types) && !in_array('golf_club', $types)) {
            return [
                'category_id' => 14, // Private club
                'confidence' => self::CONFIDENCE_HIGH,
                'reasoning' => 'Private club without golf indicates sports-focused private club',
                'matched_type' => 'private_club',
            ];
        }

        // Business complex (office building with sports facilities)
        if (in_array('office_building', $types) && 
            (in_array('gym', $types) || in_array('sports_complex', $types))) {
            return [
                'category_id' => 13, // Business complex
                'confidence' => self::CONFIDENCE_MEDIUM,
                'reasoning' => 'Office building with sports facilities indicates business complex',
                'matched_type' => 'office+sports',
            ];
        }

        // Industrial (factory/warehouse with facilities)
        $industrialTypes = ['factory', 'warehouse', 'industrial_area', 'manufacturing'];
        $hasIndustrial = count(array_intersect($types, $industrialTypes)) > 0;
        
        if ($hasIndustrial && (in_array('gym', $types) || in_array('sports_complex', $types))) {
            return [
                'category_id' => 16, // Industrial
                'confidence' => self::CONFIDENCE_MEDIUM,
                'reasoning' => 'Industrial facility with sports amenities',
                'matched_type' => 'industrial+sports',
            ];
        }

        // Hotel/Resort with sports facilities (hotel takes priority)
        if ((in_array('hotel', $types) || in_array('resort_hotel', $types)) && 
            in_array('sports_complex', $types)) {
            return [
                'category_id' => 7, // Hotel or resort
                'confidence' => self::CONFIDENCE_HIGH,
                'reasoning' => 'Hotel with sports facilities',
                'matched_type' => 'hotel+sports',
            ];
        }

        // School with sports facilities (school takes priority)
        $schoolTypes = ['school', 'primary_school', 'secondary_school', 'high_school', 'middle_school'];
        $hasSchool = count(array_intersect($types, $schoolTypes)) > 0;
        
        if ($hasSchool && in_array('sports_complex', $types)) {
            return [
                'category_id' => 3, // School
                'confidence' => self::CONFIDENCE_HIGH,
                'reasoning' => 'School with sports facilities',
                'matched_type' => 'school+sports',
            ];
        }

        // University with sports facilities (university takes priority)
        if ((in_array('university', $types) || in_array('college', $types)) && 
            in_array('sports_complex', $types)) {
            return [
                'category_id' => 8, // College or university
                'confidence' => self::CONFIDENCE_HIGH,
                'reasoning' => 'University with sports facilities',
                'matched_type' => 'university+sports',
            ];
        }

        return null; // No combination pattern matched
    }

    /**
     * Get all mapped Google Places types.
     *
     * @return array
     */
    public function getAllMappedTypes(): array
    {
        return array_keys($this->typeMapping);
    }

    /**
     * Get category ID for a specific Google Places type.
     *
     * @param string $type
     * @return int|null
     */
    public function getCategoryIdForType(string $type): ?int
    {
        return $this->typeMapping[$type]['category_id'] ?? null;
    }

    /**
     * Check if a Google Places type is mapped.
     *
     * @param string $type
     * @return bool
     */
    public function isMapped(string $type): bool
    {
        return isset($this->typeMapping[$type]);
    }
}

