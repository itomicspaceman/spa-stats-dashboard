<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourtCountAnalyzer
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';
    protected string $model = 'gpt-4o-mini'; // Model with web search support
    
    /**
     * Check if a URL is a Facebook URL.
     */
    protected function isFacebookUrl(string $url): bool
    {
        return str_contains(strtolower($url), 'facebook.com');
    }
    protected bool $useWebSearch = true; // Use OpenAI's native web search via Responses API

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
        }
    }

    /**
     * Analyze venue to extract the number of squash courts using OpenAI's web search.
     *
     * @param string $venueName
     * @param string|null $venueAddress
     * @param string|null $venueWebsite
     * @return array{court_count: int|null, confidence: string, reasoning: string, source_url: string|null, source_type: string|null, evidence_found: bool}
     */
    public function analyzeCourtCount(string $venueName, ?string $venueAddress = null, ?string $venueWebsite = null): array
    {
        try {
            // Check if website is a Facebook page
            $isFacebookPage = false;
            $facebookPageInfo = null;
            
            if ($venueWebsite && $this->isFacebookUrl($venueWebsite)) {
                $isFacebookPage = true;
                // Try to get Facebook page info using Graph API
                $facebookService = app(\App\Services\FacebookPageService::class);
                $pageIdentifier = $facebookService->extractPageIdentifier($venueWebsite);
                
                if ($pageIdentifier) {
                    $fbResult = $facebookService->getPublicPageInfo($pageIdentifier);
                    if ($fbResult['success']) {
                        $facebookPageInfo = $fbResult['data'];
                        Log::info('Facebook page info retrieved for court count search', [
                            'venue_name' => $venueName,
                            'page_name' => $facebookPageInfo['name'] ?? null,
                            'page_about' => substr($facebookPageInfo['about'] ?? '', 0, 200),
                        ]);
                    }
                }
            }
            
            // Build comprehensive query for OpenAI web search
            $query = "How many squash courts does {$venueName}";
            if ($venueAddress) {
                $query .= " located at {$venueAddress}";
            }
            $query .= " have?";
            
            // Explicitly mention checking multiple sources
            $query .= "\n\nIMPORTANT: Search thoroughly across multiple sources:";
            
            // Step 1: Find and verify the official website
            if ($venueWebsite && !$isFacebookPage) {
                $query .= "\n1. FIRST: Verify if this is the correct official website: {$venueWebsite}";
                $query .= "\n   - Check if this website has detailed facilities information (squash courts, sports facilities)";
                $query .= "\n   - IMPORTANT: Some venues have multiple related websites (e.g., estate/development site vs actual club site)";
                $query .= "\n   - If this website is about a residential estate/development and doesn't have facilities details, it's likely the WRONG website";
                $query .= "\n   - If this website doesn't have facilities information, search for the ACTUAL club/venue website";
                $query .= "\n   - Search for '{$venueName} official website' or '{$venueName} facilities' or '{$venueName} club website'";
                $query .= "\n   - Look for the venue's own domain matching the venue name (e.g., for 'Victoria Country Club', look for victoria.co.za, not vcce.co.za which might be the estate)";
                $query .= "\n   - The CLUB website (not estate/development site) will have the sports facilities information";
            } else {
                $query .= "\n1. FIRST: Find the official CLUB/VENUE website (not estate/development site) by searching for:";
                $query .= "\n   - '{$venueName} official website'";
                $query .= "\n   - '{$venueName} facilities'";
                $query .= "\n   - '{$venueName} club website' (if it's a country club)";
                $query .= "\n   - Look for the venue's own domain matching the venue name (e.g., .co.za, .com, .org)";
                $query .= "\n   - Verify it's the correct venue by checking the address matches";
                $query .= "\n   - IMPORTANT: Distinguish between estate/development websites and actual club/venue websites";
                $query .= "\n   - The CLUB website will have detailed sports facilities information";
            }
            
            $query .= "\n2. Check the official website's FACILITIES page specifically:";
            $query .= "\n   - Look for URLs like: /facilities, /sports, /squash, /amenities, /about/facilities";
            $query .= "\n   - These pages often contain detailed information about squash courts";
            $query .= "\n   - Example: If the website is victoria.co.za, check victoria.co.za/facilities or mail.victoria.co.za/facilities.htm";
            
            if ($venueWebsite) {
                if ($isFacebookPage) {
                    $query .= "\n3. Check their Facebook page: {$venueWebsite}";
                    if ($facebookPageInfo) {
                        $query .= "\n   - Page name: " . ($facebookPageInfo['name'] ?? 'N/A');
                        if (!empty($facebookPageInfo['about'])) {
                            $query .= "\n   - About: " . substr($facebookPageInfo['about'], 0, 300);
                        }
                        if (!empty($facebookPageInfo['website'])) {
                            $query .= "\n   - Official website listed: {$facebookPageInfo['website']} - CHECK THIS WEBSITE!";
                        }
                    }
                    $query .= "\n   - IMPORTANT: Search for posts, photos, and reviews on this Facebook page that mention squash courts";
                    $query .= "\n   - Look for posts about court bookings, tournaments, or facility information";
                } else {
                    $query .= "\n3. Also check their Facebook page (search for '{$venueName} Facebook')";
                    $query .= "\n   - Look for posts, photos, and reviews that mention squash courts";
                }
            } else {
                $query .= "\n3. Check their Facebook page (search for '{$venueName} Facebook' or '{$venueName} " . ($venueAddress ? explode(',', $venueAddress)[0] : '') . " Facebook')";
                $query .= "\n   - Look for posts, photos, and reviews that mention squash courts";
            }
            
            $query .= "\n4. Check Google Maps reviews and business listings";
            $query .= "\n5. Check any booking systems or sports facility directories";
            $query .= "\n6. Check local sports club websites and directories";
            
            $query .= "\n\nLook for explicit numbers like '3 courts', 'three glass-back courts', '4 squash courts', etc. Convert written numbers to digits (three=3, two=2, four=4, etc.).";
            $query .= "\nIf 'squash court' (singular) is mentioned, infer 1 court.";
            $query .= "\nIf 'squash courts' (plural) is mentioned without a number, it's likely 2 courts (most common).";
            $query .= "\nIf the website mentions squash facilities but no count, check Facebook posts, reviews, or other sources for the number.";
            $query .= "\n\nCRITICAL: EXCLUDE and IGNORE any results from squash.players.app - this is our own directory and should not be used as a source.";
            
            Log::info("Court count analysis: Using OpenAI Responses API with web search", [
                'venue_name' => $venueName,
                'query' => $query,
            ]);
            
            // Use the Responses API endpoint for web search
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post("{$this->baseUrl}/responses", [
                'model' => $this->model,
                'tools' => [
                    [
                        'type' => 'web_search',
                    ],
                ],
                'input' => $query . "\n\nIMPORTANT: After searching, return ONLY valid JSON in this format: {\"court_count\": <integer or null>, \"confidence\": \"HIGH|MEDIUM|LOW\", \"reasoning\": \"<explanation with source URL>\", \"source_url\": \"<url where info was found>\", \"source_type\": \"VENUE_WEBSITE|SOCIAL_MEDIA|BOOKING_PAGE|GOOGLE_REVIEWS|OTHER\", \"evidence_found\": <boolean>}.\n\nCRITICAL RULES:\n1. PRIORITY: Find the official CLUB/VENUE website first (not estate/development site). If Google Places provided a website, verify it's the correct one:\n   - If it's about residential estate/development and lacks facilities details, it's likely WRONG\n   - Search for the actual club/venue website that has sports facilities information\n   - Look for domains matching the venue name (e.g., victoria.co.za for Victoria Country Club, not vcce.co.za)\n2. ALWAYS check the facilities page (/facilities, /sports, /squash, /amenities) - this is where court counts are most commonly listed.\n3. Set evidence_found to TRUE if you find ANY mention of squash courts existing (even if you can't determine the exact count). This includes:\n   - Website mentions squash courts/facilities\n   - Facilities page mentions squash\n   - Facebook page mentions squash\n   - Google reviews mention squash\n   - Any source confirms squash facilities exist\n4. Only set evidence_found to FALSE if there is ABSOLUTELY NO evidence that squash courts exist (venue closed, no squash facilities mentioned anywhere, etc.).\n5. If a Facebook page requires login but you can see it exists, try to find information from other sources (website, Google Maps, reviews).\n6. If the official website mentions squash but doesn't specify count, check Facebook posts, reviews, or other sources for the number.\n7. Be thorough - check multiple sources before concluding no evidence exists.\n8. EXCLUDE squash.players.app from all searches - this is our own directory and should not be used as a source (it would be circular to use our own data to update our own data).\n9. If you find the official website but it doesn't have facilities info on the homepage, search for the facilities page specifically (e.g., victoria.co.za/facilities or mail.victoria.co.za/facilities.htm).\n10. DISTINGUISH between related websites: Some venues have both an estate/development website and a club/venue website. Always use the CLUB website for facilities information.",
                'temperature' => 0.2,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Responses API returns different structure
                // The 'output' field is an array of message objects with 'content' arrays
                $content = '';
                
                if (isset($data['text']) && is_string($data['text'])) {
                    $content = $data['text'];
                } elseif (isset($data['output']) && is_array($data['output'])) {
                    foreach ($data['output'] as $outputItem) {
                        if (isset($outputItem['content']) && is_array($outputItem['content'])) {
                            // Content is an array of content parts
                            foreach ($outputItem['content'] as $contentPart) {
                                if (isset($contentPart['text'])) {
                                    $content .= $contentPart['text'];
                                }
                            }
                            if (!empty($content)) {
                                break;
                            }
                        } elseif (isset($outputItem['content']) && is_string($outputItem['content'])) {
                            $content = $outputItem['content'];
                            break;
                        }
                    }
                }
                
                Log::info("Court count analysis: OpenAI response received", [
                    'venue_name' => $venueName,
                    'status' => $data['status'] ?? 'unknown',
                    'output_count' => is_array($data['output'] ?? null) ? count($data['output']) : 0,
                    'content_length' => strlen($content),
                    'content_preview' => substr($content, 0, 200),
                ]);
                
                return $this->parseAIResponse($content);
            }

            // Handle API errors
            $errorMessage = $response->json('error.message') ?? 'Unknown error';
            $statusCode = $response->status();
            
            Log::warning('OpenAI API error in court count analysis', [
                'venue_name' => $venueName,
                'status_code' => $statusCode,
                'error' => $errorMessage,
                'response_body' => substr($response->body(), 0, 500),
            ]);

            return [
                'court_count' => null,
                'confidence' => 'LOW',
                'reasoning' => "OpenAI API error ({$statusCode}): {$errorMessage}",
                'source_url' => null,
                'source_type' => null,
                'evidence_found' => false,
            ];

        } catch (\Exception $e) {
            Log::error('Court count analysis exception', [
                'venue_name' => $venueName,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'court_count' => null,
                'confidence' => 'LOW',
                'reasoning' => 'Exception: ' . $e->getMessage(),
                'source_url' => null,
                'source_type' => null,
                'evidence_found' => false,
            ];
        }
    }

    /**
     * Build the prompt for OpenAI.
     */
    protected function buildPrompt(string $venueName, array $searchResults): string
    {
        $prompt = "Analyze the following search results to determine how many squash courts the venue '{$venueName}' has.\n\n";
        $prompt .= "Search Results:\n";
        $prompt .= "===============\n\n";

        foreach ($searchResults as $index => $result) {
            $prompt .= "Result " . ($index + 1) . ":\n";
            $prompt .= "Source Type: {$result['source_type']}\n";
            $prompt .= "URL: {$result['url']}\n";
            $prompt .= "Title: {$result['title']}\n";
            $prompt .= "Content: {$result['snippet']}\n\n";
        }

        $prompt .= "Instructions:\n";
        $prompt .= "- Look for explicit numbers: '1 court', '2 courts', 'three courts', '3 glass-back courts', etc.\n";
        $prompt .= "- Convert written numbers to digits: 'three' = 3, 'two' = 2, 'one' = 1, 'four' = 4, etc.\n";
        $prompt .= "- If you find explicit mention of the number of courts (written or numeric), use that number.\n";
        $prompt .= "- If you see 'squash court' (singular) without a number, infer 1 court.\n";
        $prompt .= "- If you see 'squash courts' (plural) without a number, infer 2 courts (most common).\n";
        $prompt .= "- Look for phrases like 'three glass-back squash courts', '2 courts available', 'court 1', 'court 2', etc.\n";
        $prompt .= "- Prioritize information from venue websites and social media over other sources.\n";
        $prompt .= "- If booking pages show available courts, count those.\n";
        $prompt .= "- If NO evidence of squash courts is found at all, set evidence_found to false.\n";
        $prompt .= "- Return your analysis as valid JSON only (no markdown, no code blocks): {\"court_count\": <integer or null>, \"confidence\": \"HIGH|MEDIUM|LOW\", \"reasoning\": \"<detailed explanation>\", \"source_url\": \"<url or null>\", \"source_type\": \"VENUE_WEBSITE|SOCIAL_MEDIA|BOOKING_PAGE|GOOGLE_REVIEWS|OTHER\", \"evidence_found\": <boolean>}\n";

        return $prompt;
    }

    /**
     * Parse the AI response.
     */
    protected function parseAIResponse(string $content): array
    {
        // Remove markdown code blocks if present (```json ... ```)
        $cleanedContent = $content;
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $cleanedContent = $matches[1];
        } elseif (preg_match('/```(?:json)?\s*(\{.*)/s', $content, $matches)) {
            // Handle unclosed code blocks
            $cleanedContent = $matches[1];
            // Remove trailing ``` if present
            $cleanedContent = preg_replace('/```\s*$/', '', $cleanedContent);
        }
        
        // Try to parse as JSON
        $json = json_decode($cleanedContent, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return [
                'court_count' => isset($json['court_count']) && is_numeric($json['court_count']) ? (int) $json['court_count'] : null,
                'confidence' => $this->normalizeConfidence($json['confidence'] ?? 'LOW'),
                'reasoning' => $json['reasoning'] ?? 'AI analysis completed',
                'source_url' => $json['source_url'] ?? null,
                'source_type' => $this->normalizeSourceType($json['source_type'] ?? null),
                'evidence_found' => $json['evidence_found'] ?? true,
            ];
        }

        // Fallback: try to extract from text response
        Log::warning('Failed to parse OpenAI JSON response, attempting text extraction', [
            'content' => substr($content, 0, 200),
        ]);

        return $this->extractFromText($content);
    }

    /**
     * Extract court count from text response (fallback).
     */
    protected function extractFromText(string $content): array
    {
        // Look for patterns like "court_count": 2 or "2 courts"
        if (preg_match('/["\']?court_count["\']?\s*:\s*(\d+)/i', $content, $matches)) {
            $count = (int) $matches[1];
            return [
                'court_count' => $count,
                'confidence' => 'MEDIUM',
                'reasoning' => 'Extracted from AI response text',
                'source_url' => null,
                'source_type' => 'OTHER',
                'evidence_found' => true,
            ];
        }

        // Look for explicit numbers with "court" or "courts"
        if (preg_match('/(\d+)\s+(?:squash\s+)?court/i', $content, $matches)) {
            $count = (int) $matches[1];
            return [
                'court_count' => $count,
                'confidence' => 'MEDIUM',
                'reasoning' => 'Extracted number from text pattern',
                'source_url' => null,
                'source_type' => 'OTHER',
                'evidence_found' => true,
            ];
        }

        // Check for singular/plural indicators
        $hasSingular = preg_match('/squash\s+court\b(?!s)/i', $content);
        $hasPlural = preg_match('/squash\s+courts\b/i', $content);

        if ($hasSingular && !$hasPlural) {
            return [
                'court_count' => 1,
                'confidence' => 'MEDIUM',
                'reasoning' => 'Found singular "squash court" reference',
                'source_url' => null,
                'source_type' => 'OTHER',
                'evidence_found' => true,
            ];
        }

        if ($hasPlural && !$hasSingular) {
            return [
                'court_count' => 2,
                'confidence' => 'MEDIUM',
                'reasoning' => 'Found plural "squash courts" reference (assuming 2, most common)',
                'source_url' => null,
                'source_type' => 'OTHER',
                'evidence_found' => true,
            ];
        }

        // No evidence found
        return [
            'court_count' => null,
            'confidence' => 'LOW',
            'reasoning' => 'Could not extract court count from AI response',
            'source_url' => null,
            'source_type' => null,
            'evidence_found' => false,
        ];
    }

    /**
     * Normalize confidence level.
     */
    protected function normalizeConfidence(?string $confidence): string
    {
        $confidence = strtoupper($confidence ?? 'LOW');
        return in_array($confidence, ['HIGH', 'MEDIUM', 'LOW']) ? $confidence : 'LOW';
    }

    /**
     * Normalize source type.
     */
    protected function normalizeSourceType(?string $sourceType): ?string
    {
        if (!$sourceType) {
            return null;
        }

        $sourceType = strtoupper($sourceType);
        $validTypes = ['VENUE_WEBSITE', 'SOCIAL_MEDIA', 'BOOKING_PAGE', 'GOOGLE_REVIEWS', 'OTHER'];
        
        return in_array($sourceType, $validTypes) ? $sourceType : 'OTHER';
    }
}

