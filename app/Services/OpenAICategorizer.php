<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAICategorizer
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';
    protected string $model = 'gpt-4';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
        }
    }

    /**
     * Use AI to categorize a venue when Google Places mapping is insufficient.
     *
     * @param array $venueData Venue information (name, address, etc.)
     * @param array $googlePlacesData Google Places API response data
     * @param array $availableCategories List of available venue categories
     * @return array{category_id: int|null, confidence: string, reasoning: string, suggest_new_category: bool, suggested_category_name: string|null}
     */
    public function categorizeVenue(array $venueData, array $googlePlacesData, array $availableCategories): array
    {
        try {
            $prompt = $this->buildPrompt($venueData, $googlePlacesData, $availableCategories);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at categorizing squash venues. Analyze the venue information and Google Places data to determine the most appropriate category. IMPORTANT: "Dedicated facility" means a venue dedicated ONLY to squash (not multi-sport facilities). General sports complexes should be categorized as "Leisure centre" instead. Be precise and provide clear reasoning.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3, // Lower temperature for more consistent categorization
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                return $this->parseAIResponse($content, $availableCategories);
            }

            // Handle API errors
            $errorMessage = $response->json('error.message') ?? 'Unknown error';
            $statusCode = $response->status();
            
            Log::warning('OpenAI API error', [
                'venue_id' => $venueData['id'] ?? null,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ]);

            return [
                'category_id' => null,
                'confidence' => 'LOW',
                'reasoning' => "OpenAI API error ({$statusCode}): {$errorMessage}",
                'suggest_new_category' => false,
                'suggested_category_name' => null,
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI categorization exception', [
                'venue_id' => $venueData['id'] ?? null,
                'exception' => $e->getMessage(),
            ]);

            return [
                'category_id' => null,
                'confidence' => 'LOW',
                'reasoning' => 'Exception: ' . $e->getMessage(),
                'suggest_new_category' => false,
                'suggested_category_name' => null,
            ];
        }
    }

    /**
     * Build the prompt for OpenAI.
     *
     * @param array $venueData
     * @param array $googlePlacesData
     * @param array $availableCategories
     * @return string
     */
    protected function buildPrompt(array $venueData, array $googlePlacesData, array $availableCategories): string
    {
        $categoriesList = collect($availableCategories)
            ->map(fn($cat) => "- ID {$cat['id']}: {$cat['name']}")
            ->join("\n");

        $googleTypes = !empty($googlePlacesData['types']) 
            ? implode(', ', $googlePlacesData['types']) 
            : 'None';
        
        $primaryType = $googlePlacesData['primaryType'] ?? 'None';

        return <<<PROMPT
I need you to categorize a squash venue based on the following information:

**Venue Information:**
- Name: {$venueData['name']}
- Address: {$venueData['address']}
- Google Place ID: {$venueData['g_place_id']}

**Google Places Data:**
- Primary Type: {$primaryType}
- All Types: {$googleTypes}
- Display Name: {$googlePlacesData['displayName']}
- Business Status: {$googlePlacesData['businessStatus']}

**Available Categories:**
{$categoriesList}

**Instructions:**
1. Analyze the venue information and Google Places types
2. Select the MOST APPROPRIATE category from the list above
3. IMPORTANT: "Dedicated facility" (ID 5) should ONLY be used if the venue is dedicated exclusively to squash (e.g., "squash club", "squash center"). General sports facilities (sports complex, recreation center) should be "Leisure centre" (ID 2).
4. If NONE of the categories fit well, respond with "SUGGEST_NEW_CATEGORY" and propose a new category name
5. Provide your response in this exact format:

CATEGORY_ID: [number or SUGGEST_NEW_CATEGORY]
CONFIDENCE: [HIGH, MEDIUM, or LOW]
REASONING: [Your explanation in 1-2 sentences]
SUGGESTED_CATEGORY: [Only if suggesting new category, otherwise leave blank]

Be specific and concise in your reasoning.
PROMPT;
    }

    /**
     * Parse the AI response into structured data.
     *
     * @param string $content
     * @param array $availableCategories
     * @return array
     */
    protected function parseAIResponse(string $content, array $availableCategories): array
    {
        $lines = explode("\n", $content);
        $categoryId = null;
        $confidence = 'LOW';
        $reasoning = 'Unable to parse AI response';
        $suggestNewCategory = false;
        $suggestedCategoryName = null;

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (preg_match('/^CATEGORY_ID:\s*(.+)$/i', $line, $matches)) {
                $value = trim($matches[1]);
                if (strtoupper($value) === 'SUGGEST_NEW_CATEGORY') {
                    $suggestNewCategory = true;
                } elseif (is_numeric($value)) {
                    $categoryId = (int) $value;
                }
            } elseif (preg_match('/^CONFIDENCE:\s*(.+)$/i', $line, $matches)) {
                $conf = strtoupper(trim($matches[1]));
                if (in_array($conf, ['HIGH', 'MEDIUM', 'LOW'])) {
                    $confidence = $conf;
                }
            } elseif (preg_match('/^REASONING:\s*(.+)$/i', $line, $matches)) {
                $reasoning = trim($matches[1]);
            } elseif (preg_match('/^SUGGESTED_CATEGORY:\s*(.+)$/i', $line, $matches)) {
                $suggested = trim($matches[1]);
                if (!empty($suggested)) {
                    $suggestedCategoryName = $suggested;
                }
            }
        }

        // Validate category ID exists
        if ($categoryId !== null) {
            $validIds = collect($availableCategories)->pluck('id')->toArray();
            if (!in_array($categoryId, $validIds)) {
                $categoryId = null;
                $reasoning .= ' (Invalid category ID returned by AI)';
            }
        }

        return [
            'category_id' => $categoryId,
            'confidence' => $confidence,
            'reasoning' => $reasoning,
            'suggest_new_category' => $suggestNewCategory,
            'suggested_category_name' => $suggestedCategoryName,
        ];
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
}

