<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key', '');
        $this->model = (string) config('services.gemini.model', 'gemini-1.5-flash');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function generateContent(string $prompt, array $options = []): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning('Gemini API not configured; GEMINI_API_KEY missing in .env');
            return null;
        }

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => array_merge([
                'temperature' => $options['temperature'] ?? 0.3,
                'maxOutputTokens' => $options['maxOutputTokens'] ?? 1024,
            ], $options['generationConfig'] ?? []),
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout($options['timeout'] ?? 30)
            ->post($this->buildUrl(), $payload);

        if (!$response->ok()) {
            Log::warning('Gemini API error: ' . $response->body());
            return null;
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    public function extractOpportunityFromContent(string $rawContent): ?array
    {
        $prompt = $this->buildOpportunityExtractionPrompt($rawContent);
        $text = $this->generateContent($prompt, [
            'temperature' => 0.2,
            'generationConfig' => ['responseMimeType' => 'application/json'],
        ]);

        if (!$text) {
            return null;
        }

        $decoded = json_decode(trim($text), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function buildUrl(): string
    {
        return self::BASE_URL . '/models/' . $this->model . ':generateContent?key=' . $this->apiKey;
    }

    private function buildOpportunityExtractionPrompt(string $rawContent): string
    {
        $truncated = strlen($rawContent) > 8000 ? substr($rawContent, 0, 8000) . '...[truncated]' : $rawContent;

        return <<<PROMPT
Extract funding opportunity data from the following web content. Return ONLY valid JSON with these exact keys (use null for missing values):

{
  "title": "string - opportunity title",
  "funding_type": "string - one of: grant, equity, debt, prize, other",
  "deadline": "string - YYYY-MM-DD format or null",
  "industry": "string or null",
  "stage": "string or null - e.g. seed, series-a, growth",
  "funding_min": "number or null - minimum funding amount",
  "funding_max": "number or null - maximum funding amount"
}

Rules:
- deadline must be in the future (today or later), use null if past or unclear
- funding amounts: extract numbers, convert "10k" to 10000, "1M" to 1000000
- Return ONLY the JSON object, no markdown or explanation

Content to analyze:
{$truncated}
PROMPT;
    }
}
