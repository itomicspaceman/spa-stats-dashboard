<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourtCountSearcher
{
    protected array $apis;
    protected ?string $openAIApiKey;

    public function __construct()
    {
        $this->apis = [
            'google_custom_search' => [
                'enabled' => !empty(config('services.google_custom_search.api_key')) && !empty(config('services.google_custom_search.engine_id')),
                'api_key' => config('services.google_custom_search.api_key'),
                'engine_id' => config('services.google_custom_search.engine_id'),
            ],
            'serpapi' => [
                'enabled' => !empty(config('services.serpapi.api_key')),
                'api_key' => config('services.serpapi.api_key'),
            ],
            'tavily' => [
                'enabled' => !empty(config('services.tavily.api_key')),
                'api_key' => config('services.tavily.api_key'),
            ],
        ];

        $this->openAIApiKey = config('services.openai.api_key');
    }

    /**
     * Search for court count information about a venue.
     *
     * @param string $venueName
     * @param string|null $venueAddress
     * @param string|null $venueWebsite
     * @return array ['success' => bool, 'results' => array, 'api_used' => string|null, 'error' => string|null]
     */
    public function searchForCourtCount(string $venueName, ?string $venueAddress = null, ?string $venueWebsite = null): array
    {
        // If we have the venue's website, try to fetch it directly first (highest priority source)
        if ($venueWebsite) {
            Log::info("Court count search: Venue has website, attempting direct fetch", [
                'venue_name' => $venueName,
                'website' => $venueWebsite,
            ]);
            
            try {
                $websiteContent = $this->fetchWebsiteContent($venueWebsite);
                if ($websiteContent) {
                    // Return the website as a search result with highest priority
                    return [
                        'success' => true,
                        'results' => [
                            [
                                'title' => $venueName,
                                'snippet' => $websiteContent,
                                'url' => $venueWebsite,
                                'source_type' => 'VENUE_WEBSITE',
                            ]
                        ],
                        'api_used' => 'direct_website_fetch',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Court count search: Failed to fetch venue website directly", [
                    'venue_name' => $venueName,
                    'website' => $venueWebsite,
                    'error' => $e->getMessage(),
                ]);
                // Continue to search APIs as fallback
            }
        }
        
        // Build search query
        $query = $this->buildSearchQuery($venueName, $venueAddress);
        
        Log::info("Court count search starting", [
            'venue_name' => $venueName,
            'query' => $query,
            'website' => $venueWebsite,
            'available_apis' => array_keys(array_filter($this->apis, fn($api) => $api['enabled'])),
        ]);
        
        // Try each API in order until one succeeds
        foreach ($this->apis as $apiName => $config) {
            if (!$config['enabled']) {
                Log::debug("Court count search: API {$apiName} is disabled", [
                    'venue_name' => $venueName,
                ]);
                continue;
            }

            try {
                Log::info("Court count search: Trying {$apiName}", [
                    'venue_name' => $venueName,
                    'query' => $query,
                ]);
                
                $result = $this->searchWithApi($apiName, $query, $venueName, $venueWebsite);
                
                if ($result['success']) {
                    Log::info("Court count search succeeded using {$apiName}", [
                        'venue_name' => $venueName,
                        'api_used' => $apiName,
                        'results_count' => count($result['results'] ?? []),
                        'first_result_url' => $result['results'][0]['url'] ?? null,
                    ]);
                    return $result;
                } else {
                    Log::warning("Court count search: {$apiName} returned unsuccessful result", [
                        'venue_name' => $venueName,
                        'api_used' => $apiName,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("Court count search failed with {$apiName}", [
                    'venue_name' => $venueName,
                    'api_used' => $apiName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                continue; // Try next API
            }
        }

        // All APIs failed
        Log::error("All court count search APIs failed", [
            'venue_name' => $venueName,
            'query' => $query,
            'website' => $venueWebsite,
            'available_apis' => array_keys(array_filter($this->apis, fn($api) => $api['enabled'])),
        ]);

        return [
            'success' => false,
            'results' => [],
            'api_used' => null,
            'error' => 'All search APIs failed or are unavailable',
        ];
    }

    /**
     * Build search query from venue name and address.
     */
    protected function buildSearchQuery(string $venueName, ?string $venueAddress = null): string
    {
        $query = "{$venueName} squash courts";
        
        if ($venueAddress) {
            $query .= " {$venueAddress}";
        }
        
        // Exclude our own directory to avoid circular references
        $query .= " -site:squash.players.app";
        
        return $query;
    }

    /**
     * Search using a specific API.
     */
    protected function searchWithApi(string $apiName, string $query, string $venueName, ?string $venueWebsite = null): array
    {
        return match ($apiName) {
            'google_custom_search' => $this->searchWithGoogleCustomSearch($query, $venueName, $venueWebsite),
            'serpapi' => $this->searchWithSerpApi($query, $venueName, $venueWebsite),
            'tavily' => $this->searchWithTavily($query, $venueName, $venueWebsite),
            default => ['success' => false, 'results' => [], 'error' => "Unknown API: {$apiName}"],
        };
    }

    /**
     * Search using Google Custom Search API.
     */
    protected function searchWithGoogleCustomSearch(string $query, string $venueName, ?string $venueWebsite = null): array
    {
        $config = $this->apis['google_custom_search'];
        
        $response = Http::get('https://www.googleapis.com/customsearch/v1', [
            'key' => $config['api_key'],
            'cx' => $config['engine_id'],
            'q' => $query,
            'num' => 10, // Get up to 10 results
        ]);

        if (!$response->successful()) {
            // Check if it's a quota/credit issue
            $body = $response->json();
            if (isset($body['error']['code']) && $body['error']['code'] === 429) {
                throw new \Exception('Google Custom Search API quota exceeded');
            }
            
            return [
                'success' => false,
                'results' => [],
                'error' => $response->body(),
            ];
        }

        $data = $response->json();
        $results = [];

        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $url = $item['link'] ?? '';
                // Filter out our own directory
                if (str_contains(strtolower($url), 'squash.players.app')) {
                    continue;
                }
                $results[] = [
                    'title' => $item['title'] ?? '',
                    'snippet' => $item['snippet'] ?? '',
                    'url' => $url,
                    'source_type' => $this->determineSourceType($url, $venueWebsite),
                ];
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'api_used' => 'google_custom_search',
        ];
    }

    /**
     * Search using SERP API.
     */
    protected function searchWithSerpApi(string $query, string $venueName, ?string $venueWebsite = null): array
    {
        $config = $this->apis['serpapi'];
        
        $response = Http::get('https://serpapi.com/search', [
            'api_key' => $config['api_key'],
            'q' => $query,
            'engine' => 'google',
            'num' => 10,
        ]);

        if (!$response->successful()) {
            $body = $response->json();
            if (isset($body['error']) && str_contains(strtolower($body['error']), 'quota')) {
                throw new \Exception('SERP API quota exceeded');
            }
            
            return [
                'success' => false,
                'results' => [],
                'error' => $response->body(),
            ];
        }

        $data = $response->json();
        $results = [];

        if (isset($data['organic_results'])) {
            foreach ($data['organic_results'] as $item) {
                $url = $item['link'] ?? '';
                // Filter out our own directory
                if (str_contains(strtolower($url), 'squash.players.app')) {
                    continue;
                }
                $results[] = [
                    'title' => $item['title'] ?? '',
                    'snippet' => $item['snippet'] ?? '',
                    'url' => $url,
                    'source_type' => $this->determineSourceType($url, $venueWebsite),
                ];
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'api_used' => 'serpapi',
        ];
    }

    /**
     * Search using Tavily API.
     */
    protected function searchWithTavily(string $query, string $venueName, ?string $venueWebsite = null): array
    {
        $config = $this->apis['tavily'];
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://api.tavily.com/search', [
            'api_key' => $config['api_key'],
            'query' => $query,
            'max_results' => 10,
            'include_domains' => $venueWebsite ? [parse_url($venueWebsite, PHP_URL_HOST)] : null,
        ]);

        if (!$response->successful()) {
            $body = $response->json();
            if (isset($body['error']) && (str_contains(strtolower($body['error']), 'quota') || str_contains(strtolower($body['error']), 'limit'))) {
                throw new \Exception('Tavily API quota exceeded');
            }
            
            return [
                'success' => false,
                'results' => [],
                'error' => $response->body(),
            ];
        }

        $data = $response->json();
        $results = [];

        if (isset($data['results'])) {
            foreach ($data['results'] as $item) {
                $url = $item['url'] ?? '';
                // Filter out our own directory
                if (str_contains(strtolower($url), 'squash.players.app')) {
                    continue;
                }
                $results[] = [
                    'title' => $item['title'] ?? '',
                    'snippet' => $item['content'] ?? '',
                    'url' => $url,
                    'source_type' => $this->determineSourceType($url, $venueWebsite),
                ];
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'api_used' => 'tavily',
        ];
    }

    /**
     * Determine the source type of a URL.
     */
    protected function determineSourceType(string $url, ?string $venueWebsite = null): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return 'OTHER';
        }

        $host = strtolower($host);

        // Check if it's the venue's own website
        if ($venueWebsite) {
            $venueHost = strtolower(parse_url($venueWebsite, PHP_URL_HOST));
            if ($venueHost && $host === $venueHost) {
                return 'VENUE_WEBSITE';
            }
        }

        // Check for social media
        $socialMediaDomains = ['facebook.com', 'instagram.com', 'twitter.com', 'x.com', 'linkedin.com'];
        foreach ($socialMediaDomains as $domain) {
            if (str_contains($host, $domain)) {
                return 'SOCIAL_MEDIA';
            }
        }

        // Check for booking pages
        $bookingKeywords = ['book', 'booking', 'reserve', 'reservation', 'court', 'schedule'];
        $urlLower = strtolower($url);
        foreach ($bookingKeywords as $keyword) {
            if (str_contains($urlLower, $keyword)) {
                return 'BOOKING_PAGE';
            }
        }

        // Check for Google reviews
        if (str_contains($host, 'google.com') && str_contains($url, 'review')) {
            return 'GOOGLE_REVIEWS';
        }

        return 'OTHER';
    }

    /**
     * Fetch content directly from a website URL.
     * Extracts text content for AI analysis.
     *
     * @param string $url
     * @return string|null
     */
    protected function fetchWebsiteContent(string $url): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);
            
            if (!$response->successful()) {
                return null;
            }
            
            $html = $response->body();
            
            // Extract text content from HTML (simple approach - remove tags)
            $text = strip_tags($html);
            
            // Clean up whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            // Limit to first 5000 characters to avoid token limits
            if (strlen($text) > 5000) {
                $text = substr($text, 0, 5000) . '...';
            }
            
            Log::info("Court count search: Successfully fetched website content", [
                'url' => $url,
                'content_length' => strlen($text),
            ]);
            
            return $text;
            
        } catch (\Exception $e) {
            Log::warning("Court count search: Failed to fetch website content", [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

