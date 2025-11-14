<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesTextSearchService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://places.googleapis.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.google_places.api_key');
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Google Places API key is not configured. Please set GOOGLE_PLACES_API_KEY in your .env file.');
        }
    }

    /**
     * Find a place by name and address using Text Search API.
     * This is used as a fallback when a Place ID is expired.
     *
     * @param Venue $venue The venue to search for
     * @return string|null Returns the Place ID if found, null otherwise
     */
    public function findPlaceByNameAndAddress(Venue $venue): ?string
    {
        try {
            // Build search query from venue details
            $queryParts = array_filter([
                $venue->name,
                $venue->physical_address,
                $venue->suburb,
                $venue->state,
                $venue->country->name ?? null,
            ]);
            
            $textQuery = implode(', ', $queryParts);
            
            // Build request payload
            $payload = [
                'textQuery' => $textQuery,
            ];
            
            // Add location bias if coordinates are available
            if ($venue->latitude && $venue->longitude) {
                $payload['locationBias'] = [
                    'circle' => [
                        'center' => [
                            'latitude' => (float) $venue->latitude,
                            'longitude' => (float) $venue->longitude,
                        ],
                        'radius' => 5000.0, // 5km radius
                    ],
                ];
            }
            
            Log::info('Attempting Text Search for venue', [
                'venue_id' => $venue->id,
                'query' => $textQuery,
                'has_coordinates' => isset($payload['locationBias']),
            ]);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress',
            ])->post("{$this->baseUrl}/places:searchText", $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                $places = $data['places'] ?? [];
                
                if (!empty($places)) {
                    $firstPlace = $places[0];
                    $placeId = $firstPlace['id'] ?? null;
                    
                    if ($placeId) {
                        Log::info('Text Search found place', [
                            'venue_id' => $venue->id,
                            'new_place_id' => $placeId,
                            'display_name' => $firstPlace['displayName']['text'] ?? 'N/A',
                            'address' => $firstPlace['formattedAddress'] ?? 'N/A',
                            'total_results' => count($places),
                        ]);
                        
                        return $placeId;
                    }
                }
                
                Log::warning('Text Search returned no results', [
                    'venue_id' => $venue->id,
                    'query' => $textQuery,
                ]);
                
                return null;
            }
            
            // Handle API errors
            $errorMessage = $response->json('error.message') ?? 'Unknown error';
            $statusCode = $response->status();
            
            Log::warning('Text Search API error', [
                'venue_id' => $venue->id,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Text Search exception', [
                'venue_id' => $venue->id,
                'exception' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Test the Text Search API with a known query.
     *
     * @param string $testQuery Optional test query
     * @return array{success: bool, message: string, place_id: string|null}
     */
    public function testConnection(string $testQuery = 'Googleplex, Mountain View, CA'): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.id,places.displayName',
            ])->post("{$this->baseUrl}/places:searchText", [
                'textQuery' => $testQuery,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $places = $data['places'] ?? [];
                
                if (!empty($places)) {
                    return [
                        'success' => true,
                        'message' => 'Text Search API connection successful',
                        'place_id' => $places[0]['id'] ?? null,
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Text Search API returned no results',
                    'place_id' => null,
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Text Search API connection failed: ' . $response->status(),
                'place_id' => null,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Text Search API exception: ' . $e->getMessage(),
                'place_id' => null,
            ];
        }
    }
}


