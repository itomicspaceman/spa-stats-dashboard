<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTranslateService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct()
    {
        $this->apiKey = config('services.google_translate.api_key');
        
        if (empty($this->apiKey)) {
            // Not critical - translation is optional fallback
            Log::warning('Google Translate API key not configured. Translation fallback will be disabled.');
        }
    }

    /**
     * Check if the service is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Translate text to English.
     * Uses caching to avoid duplicate translations.
     *
     * @param string $text Text to translate
     * @param string|null $sourceLanguage Optional source language code (auto-detect if null)
     * @return string|null Translated text or null on failure
     */
    public function translateToEnglish(string $text, ?string $sourceLanguage = null): ?string
    {
        if (empty($text)) {
            return null;
        }

        if (!$this->isConfigured()) {
            return null;
        }

        // Check cache first
        $cacheKey = 'translate:' . md5($text . ($sourceLanguage ?? 'auto'));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $params = [
                'key' => $this->apiKey,
                'q' => $text,
                'target' => 'en',
            ];

            if ($sourceLanguage) {
                $params['source'] = $sourceLanguage;
            }

            $response = Http::asForm()->post($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $translatedText = $data['data']['translations'][0]['translatedText'] ?? null;

                if ($translatedText) {
                    // Cache for 30 days (translations don't change)
                    Cache::put($cacheKey, $translatedText, now()->addDays(30));
                    
                    Log::info('Text translated successfully', [
                        'original_length' => strlen($text),
                        'translated_length' => strlen($translatedText),
                        'source_language' => $data['data']['translations'][0]['detectedSourceLanguage'] ?? 'unknown',
                    ]);

                    return $translatedText;
                }
            }

            $errorMessage = $response->json('error.message') ?? 'Unknown error';
            Log::warning('Translation API error', [
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Translation exception', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Detect the language of text.
     *
     * @param string $text Text to detect language for
     * @return string|null Language code or null on failure
     */
    public function detectLanguage(string $text): ?string
    {
        if (empty($text) || !$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::asForm()->post('https://translation.googleapis.com/language/translate/v2/detect', [
                'key' => $this->apiKey,
                'q' => $text,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['detections'][0][0]['language'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Language detection exception', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if text appears to be in a non-English language.
     * Simple heuristic: checks for non-ASCII characters.
     *
     * @param string $text Text to check
     * @return bool True if text appears non-English
     */
    public function appearsNonEnglish(string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        // Check if text contains non-ASCII characters (likely non-English)
        // This is a simple heuristic - not perfect but fast
        return preg_match('/[^\x00-\x7F]/', $text) === 1;
    }
}


