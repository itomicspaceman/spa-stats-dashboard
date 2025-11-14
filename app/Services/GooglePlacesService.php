<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
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
     * Get place details from Google Places (New) API.
     *
     * @param string $placeId The Google Place ID
     * @param string|null $languageCode Optional language code (e.g., 'en' for English). Defaults to null (uses API default).
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getPlaceDetails(string $placeId, ?string $languageCode = null): array
    {
        try {
            $headers = [
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'id,types,primaryType,displayName,formattedAddress,businessStatus,location,editorialSummary',
            ];
            
            // Add language code if provided (requests English names when available)
            if ($languageCode) {
                $headers['X-Goog-Language-Code'] = $languageCode;
            }
            
            $response = Http::withHeaders($headers)->get("{$this->baseUrl}/places/{$placeId}");

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'data' => [
                        'id' => $data['id'] ?? null,
                        'primaryType' => $data['primaryType'] ?? null,
                        'types' => $data['types'] ?? [],
                        'displayName' => $data['displayName']['text'] ?? null,
                        'formattedAddress' => $data['formattedAddress'] ?? null,
                        'businessStatus' => $data['businessStatus'] ?? null,
                        'location' => $data['location'] ?? null,
                        'editorialSummary' => $data['editorialSummary']['text'] ?? null,
                    ],
                    'error' => null,
                ];
            }

            // Handle API errors
            $errorMessage = $response->json('error.message') ?? 'Unknown error';
            $statusCode = $response->status();
            
            Log::warning('Google Places API error', [
                'place_id' => $placeId,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => "API error ({$statusCode}): {$errorMessage}",
            ];

        } catch (\Exception $e) {
            Log::error('Google Places API exception', [
                'place_id' => $placeId,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh/validate a Place ID using Google's free method.
     * Uses minimal field mask (only 'id') to check if Place ID is still valid
     * and get the new Place ID if it has changed.
     *
     * @param string $oldPlaceId The existing Place ID to validate
     * @return string|null Returns new Place ID if changed, original if still valid, null if expired
     */
    public function refreshPlaceId(string $oldPlaceId): ?string
    {
        try {
            // Use minimal field mask to get free Place ID validation
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'id',
            ])->get("{$this->baseUrl}/places/{$oldPlaceId}");
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['id'] ?? null; // Returns new Place ID if changed, or original if same
            }
            
            // Place ID is expired/invalid
            Log::info('Place ID validation failed', [
                'place_id' => $oldPlaceId,
                'status' => $response->status(),
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Place ID refresh exception', [
                'place_id' => $oldPlaceId,
                'exception' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Check if the API key is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Test the API connection with a known place ID.
     *
     * @param string $testPlaceId Optional test place ID (defaults to Googleplex)
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $testPlaceId = 'ChIJj61dQgK6j4AR4GeTYWZsKWw'): array
    {
        $result = $this->getPlaceDetails($testPlaceId);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Google Places API connection successful',
            ];
        }

        return [
            'success' => false,
            'message' => 'Google Places API connection failed: ' . $result['error'],
        ];
    }
}

