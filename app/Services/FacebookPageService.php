<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPageService
{
    protected ?string $accessToken;
    protected string $baseUrl = 'https://graph.facebook.com/v21.0';

    public function __construct()
    {
        // Facebook Graph API allows access to public pages without authentication
        // But for posts/comments, we'd need an access token
        $this->accessToken = config('services.facebook.access_token');
    }

    /**
     * Extract Facebook page username or ID from a Facebook URL.
     *
     * @param string $facebookUrl
     * @return string|null
     */
    public function extractPageIdentifier(string $facebookUrl): ?string
    {
        // Handle various Facebook URL formats:
        // https://www.facebook.com/kc.kingclub/
        // https://www.facebook.com/pages/THE-KING-CLUB/85044902713
        // https://facebook.com/venue-name
        
        if (preg_match('/facebook\.com\/(?:pages\/[^\/]+\/)?([^\/\?]+)/i', $facebookUrl, $matches)) {
            return trim($matches[1], '/');
        }
        
        return null;
    }

    /**
     * Get public page information from Facebook Graph API.
     * Works without authentication for public pages.
     *
     * @param string $pageIdentifier Username or page ID
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getPublicPageInfo(string $pageIdentifier): array
    {
        try {
            // Public pages can be accessed without authentication
            // Fields available without auth: name, about, website, phone, location, etc.
            $fields = 'name,about,website,phone,location,link';
            
            $url = "{$this->baseUrl}/{$pageIdentifier}";
            $params = [
                'fields' => $fields,
            ];
            
            // Add access token if available (for posts/comments access)
            if ($this->accessToken) {
                $params['access_token'] = $this->accessToken;
            }
            
            $response = Http::timeout(10)->get($url, $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Facebook page info retrieved', [
                    'page_identifier' => $pageIdentifier,
                    'page_name' => $data['name'] ?? null,
                ]);
                
                return [
                    'success' => true,
                    'data' => $data,
                    'error' => null,
                ];
            }
            
            $error = $response->json('error') ?? ['message' => 'Unknown error'];
            
            Log::warning('Facebook Graph API error', [
                'page_identifier' => $pageIdentifier,
                'error' => $error['message'] ?? 'Unknown error',
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'error' => $error['message'] ?? 'Unknown error',
            ];
            
        } catch (\Exception $e) {
            Log::error('Facebook page service exception', [
                'page_identifier' => $pageIdentifier,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a URL is a Facebook page URL.
     *
     * @param string $url
     * @return bool
     */
    public function isFacebookUrl(string $url): bool
    {
        return str_contains(strtolower($url), 'facebook.com');
    }

    /**
     * Get page posts (requires access token).
     * This is optional - we can use OpenAI web search as fallback.
     *
     * @param string $pageIdentifier
     * @param int $limit
     * @return array
     */
    public function getPagePosts(string $pageIdentifier, int $limit = 10): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Access token required for posts',
            ];
        }
        
        try {
            $url = "{$this->baseUrl}/{$pageIdentifier}/posts";
            $params = [
                'fields' => 'message,created_time',
                'limit' => $limit,
                'access_token' => $this->accessToken,
            ];
            
            $response = Http::timeout(10)->get($url, $params);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                    'error' => null,
                ];
            }
            
            return [
                'success' => false,
                'data' => null,
                'error' => $response->json('error.message', 'Unknown error'),
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}

