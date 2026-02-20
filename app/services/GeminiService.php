<?php



namespace App\services;

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
        $this->model = (string) config('services.gemini.model', 'gemini-2.0-flash');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function generateContent(string $prompt, array $options = []): ?string
    {
        if (! $this->isConfigured()) {
            Log::error('Gemini API not configured; GEMINI_API_KEY missing in .env');
            return null;
        }

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => array_merge([
                'temperature' => $options['temperature'] ?? 0.3,
                'maxOutputTokens' => $options['maxOutputTokens'] ?? 1024,
            ], $options['generationConfig'] ?? []),
        ];

        if (! empty($options['tools'])) {
            $payload['tools'] = $options['tools'];
        }

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout($options['timeout'] ?? 30)
            ->post($this->buildUrl(), $payload);

        if (! $response->ok()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    /**
     * Search for opportunity URLs via Gemini with Google Search grounding.
     * Falls back to model knowledge if grounding returns no URLs.
     *
     * @return array<string>
     */
    public function searchOpportunityUrls(string $query): array
    {
        if (! $this->isConfigured()) {
            Log::error('Gemini API not configured; set GEMINI_API_KEY in .env');
            return [];
        }

        $normalized = $this->normalizeSearchQuery($query);
        $searchQuery = $this->expandQueryWithGemini($normalized) ?? $normalized;

        $urls = $this->searchWithGrounding($searchQuery);
        if (empty($urls)) {
            $urls = $this->searchFromKnowledge($searchQuery);
        }
        if (empty($urls) && $searchQuery !== $normalized) {
            $urls = $this->searchWithGrounding($normalized);
            if (empty($urls)) {
                $urls = $this->searchFromKnowledge($normalized);
            }
        }
        if (empty($urls)) {
            $urls = $this->getQuerySpecificUrls($normalized);
        }

        return array_values(array_unique(array_slice($urls, 0, 15)));
    }

    /**
     * Use Gemini to expand short/vague queries into better search terms.
     */
    private function expandQueryWithGemini(string $query): ?string
    {
        if (str_word_count($query) >= 5) {
            return null;
        }

        $prompt = <<<PROMPT
Rewrite this search query for finding funding opportunities (grants, hackathons, prizes, equity) into a more effective web search. Keep it concise (5-12 words). Fix typos. Add relevant keywords.

Input: "{$query}"
Output: Only the rewritten query, nothing else. No quotes, no explanation.
PROMPT;

        $result = $this->generateContent($prompt, [
            'temperature' => 0.2,
            'maxOutputTokens' => 64,
        ]);

        $expanded = trim($result ?? '');
        return $expanded !== '' && $expanded !== $query ? $expanded : null;
    }

    private function normalizeSearchQuery(string $query): string
    {
        $q = trim($query);
        $typos = [
            'hackaton' => 'hackathon',
            'hackatons' => 'hackathons',
            'subvention' => 'grant',
            'subventions' => 'grants',
        ];
        foreach ($typos as $typo => $correct) {
            $q = preg_replace('/\b' . preg_quote($typo, '/') . '\b/ui', $correct, $q);
        }
        return $q;
    }

    /**
     * Query-specific URLs when Gemini returns nothing. Matches query keywords.
     *
     * @return array<string>
     */
    private function getQuerySpecificUrls(string $query): array
    {
        $lower = strtolower($query);
        if (str_contains($lower, 'hackathon')) {
            return [
                'https://devpost.com/hackathons',
                'https://hackathon.com',
                'https://mlh.io/seasons/2025/events',
                'https://www.eventbrite.com/d/online/hackathon/',
            ];
        }
        if (str_contains($lower, 'grant') || str_contains($lower, 'subvention')) {
            return [
                'https://www.f6s.com/opportunities',
                'https://www.fundsforngos.org/',
                'https://www.grantwatch.com/',
                'https://opportunitydesk.org/',
            ];
        }
        return [
            'https://www.f6s.com/opportunities',
            'https://devpost.com/hackathons',
            'https://opportunitydesk.org/',
            'https://vc4a.com/opportunities/',
        ];
    }

    /**
     * Try Google Search grounding first.
     *
     * @return array<string>
     */
    private function searchWithGrounding(string $query): array
    {
        $prompt = <<<PROMPT
Find web page URLs that list funding opportunities (grants, hackathons, prizes, equity, competitions) for: {$query}

Return a JSON array of 10-15 URLs. Include: Devpost, F6S, hackathon.com, Crunchbase, government portals, aggregators.
Only valid https URLs. Example: ["https://devpost.com/hackathons", "https://..."]
PROMPT;

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'tools' => [['google_search' => (object) []]],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, no-store',
            'Pragma' => 'no-cache',
        ])->timeout(60)->post($this->buildUrl(), $payload);

        if (! $response->ok()) {
            Log::warning('Gemini grounding error', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            return [];
        }

        $data = $response->json();
        $urls = [];

        $candidate = $data['candidates'][0] ?? null;
        if ($candidate) {
            $groundingMetadata = $candidate['groundingMetadata'] ?? null;
            if ($groundingMetadata && ! empty($groundingMetadata['groundingChunks'])) {
                foreach ($groundingMetadata['groundingChunks'] as $chunk) {
                    $uri = $chunk['web']['uri'] ?? $chunk['retrievedContext']['uri'] ?? $chunk['uri'] ?? null;
                    if ($uri && filter_var($uri, FILTER_VALIDATE_URL)) {
                        $urls[] = $uri;
                    }
                }
            }
            $text = $candidate['content']['parts'][0]['text'] ?? null;
            if ($text) {
                $urls = array_merge($urls, $this->extractUrlsFromText($text));
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Fallback: ask Gemini to return URLs from its knowledge (no grounding).
     *
     * @return array<string>
     */
    private function searchFromKnowledge(string $query): array
    {
        $prompt = <<<PROMPT
You know funding opportunity websites. Return a JSON array of URLs that list opportunities matching: {$query}

Sites: Devpost (hackathons), F6S, Crunchbase, hackathon.com, MLH, fundsforngos, VC4A, government grant portals, Eventbrite.
Return 8-15 URLs as JSON array. Only https. Example: ["https://devpost.com/hackathons", "https://..."]
PROMPT;

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(45)
            ->post($this->buildUrl(), $payload);

        if (! $response->ok()) {
            Log::warning('Gemini knowledge fallback error', ['status' => $response->status()]);
            return [];
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (! $text) {
            return [];
        }

        return $this->extractUrlsFromText($text);
    }

    /**
     * Extract valid URLs from text (JSON array or plain URLs).
     *
     * @return array<string>
     */
    private function extractUrlsFromText(string $text): array
    {
        $urls = [];
        $decoded = json_decode(trim($text), true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $url = is_string($item) ? $item : ($item['url'] ?? $item['uri'] ?? null);
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    $urls[] = $url;
                }
            }
        }
        if (empty($urls) && preg_match_all('#https?://[^\s"\'\]<>]+#', $text, $matches)) {
            foreach ($matches[0] as $url) {
                $url = rtrim($url, '.,;:)]');
                if (filter_var($url, FILTER_VALIDATE_URL) && ! in_array($url, $urls)) {
                    $urls[] = $url;
                }
            }
        }
        return $urls;
    }

    /**
     * Curated list of funding aggregators when Gemini returns nothing.
     *
     * @return array<string>
     */
    private function getDefaultAggregatorUrls(): array
    {
        return [
            'https://www.f6s.com/opportunities',
            'https://www.crunchbase.com/organizations',
            'https://vc4a.com/opportunities/',
            'https://www.africanbusinesscentral.com/grants/',
            'https://www.grantwatch.com/',
            'https://www.fundsforngos.org/',
            'https://opportunitydesk.org/',
            'https://www.youngafricanleaders.gov/',
        ];
    }

    /**
     * PHP preprocessing: strip scripts, styles, decode entities, extract readable text.
     * Removes noise like "var checkoutExternalUrls = [ &#039;/checkout-external&#039;..."
     */
    private function preprocessRawContent(string $rawContent): string
    {
        $content = $rawContent;
        $content = preg_replace('/<script[^>]*>[\s\S]*?<\/script>/ui', ' ', $content);
        $content = preg_replace('/<style[^>]*>[\s\S]*?<\/style>/ui', ' ', $content);
        $content = preg_replace('/<noscript[^>]*>[\s\S]*?<\/noscript>/ui', ' ', $content);
        $content = preg_replace('/<!--[\s\S]*?-->/u', ' ', $content);
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/\s{3,}/', '  ', $content);
        $content = trim($content);

        return strlen($content) > 100 ? $content : $rawContent;
    }

    /**
     * Preprocess with Gemini: extract main content, remove noise (nav, footer, ads).
     */
    public function preprocessContent(string $rawContent): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $truncated = strlen($rawContent) > 12000 ? substr($rawContent, 0, 12000) . '...[truncated]' : $rawContent;

        $prompt = <<<PROMPT
You are given raw HTML or text from a webpage about a funding opportunity. Your task is to extract and return ONLY the main content relevant to the opportunity.

Remove:
- Navigation menus, headers, footers
- Ads, scripts, style tags
- Sidebars, cookie banners
- Duplicate or irrelevant text

Keep:
- Title and headings
- Description of the opportunity
- Funding amounts, deadlines, eligibility
- Key dates and requirements

Return ONLY the cleaned content as plain text. No explanations, no markdown. Preserve the structure of important information.
PROMPT;

        $prompt .= "\n\nContent to preprocess:\n{$truncated}";

        return $this->generateContent($prompt, [
            'temperature' => 0.1,
            'maxOutputTokens' => 4096,
        ]);
    }

    public function extractOpportunityFromContent(string $rawContent): ?array
    {
        $cleaned = $this->preprocessRawContent($rawContent);
        $contentToExtract = $this->preprocessContent($cleaned) ?? $cleaned;

        $prompt = $this->buildOpportunityExtractionPrompt($contentToExtract);
        $text = $this->generateContent($prompt, [
            'temperature' => 0.2,
            'maxOutputTokens' => 4096,
            'generationConfig' => array_merge(
                ['responseMimeType' => 'application/json'],
                $this->getExtractionResponseSchema(),
            ),
        ]);

        if (! $text) {
            return null;
        }

        $decoded = json_decode(trim($text), true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * JSON schema for structured extraction (Gemini responseSchema).
     * Aligné sur la base de données : docs/gemini-opportunity-schema.md
     *
     * @return array<string, mixed>
     */
    private function getExtractionResponseSchema(): array
    {
        return [
            'responseSchema' => [
                'type' => 'OBJECT',
                'properties' => [
                    'title' => [
                        'type' => 'STRING',
                        'description' => 'REQUIRED. Opportunity name only. Plain text. Decode HTML entities (&#8211;→-, &amp;→&). If title is "SiteName – Subtitle", extract only the opportunity name (e.g. "Grants, Resources for Sustainability"). Never include site name or marketing slogans.',
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'REQUIRED. Full description: location, organizer, purpose, eligibility, dates. 500-2500 chars. Be thorough. For hackathons: location, organizer, date range, objectives, how to participate.',
                    ],
                    'funding_type' => [
                        'type' => 'STRING',
                        'description' => 'REQUIRED. grant | equity | debt | prize | other. Hackathons, competitions, challenges → prize.',
                    ],
                    'deadline' => [
                        'type' => 'STRING',
                        'description' => 'YYYY-MM-DD. For date ranges (e.g. "Du 1 octobre 2025 au 30 avril 2026"): use the END date → 2026-04-30. Parse French months: janvier=01, février=02, mars=03, avril=04, mai=05, juin=06, juillet=07, août=08, septembre=09, octobre=10, novembre=11, décembre=12. null if no date found.',
                    ],
                    'industry' => [
                        'type' => 'STRING',
                        'description' => 'Sector (tech, health, etc.) or null',
                    ],
                    'stage' => [
                        'type' => 'STRING',
                        'description' => 'seed, series-a, growth, or null',
                    ],
                    'funding_min' => [
                        'type' => 'NUMBER',
                        'description' => 'Numeric. 10k→10000, 1M→1000000. null if unknown.',
                    ],
                    'funding_max' => [
                        'type' => 'NUMBER',
                        'description' => 'Numeric. Same rules as funding_min.',
                    ],
                ],
                'required' => ['title', 'funding_type'],
            ],
        ];
    }

    private function buildUrl(): string
    {
        return self::BASE_URL . '/models/' . $this->model . ':generateContent?key=' . $this->apiKey;
    }

    private function buildOpportunityExtractionPrompt(string $rawContent): string
    {
        $truncated = strlen($rawContent) > 12000 ? substr($rawContent, 0, 12000) . '...[truncated]' : $rawContent;

        return <<<'PROMPT'
Extract funding opportunity data. Return ONLY valid JSON matching this schema (see docs/gemini-opportunity-schema.md):

## OUTPUT SCHEMA (database-aligned)

```json
{
  "title": "string",
  "description": "string | null",
  "funding_type": "string",
  "deadline": "string | null",
  "industry": "string | null",
  "stage": "string | null",
  "funding_min": number | null,
  "funding_max": number | null
}
```

## FIELD RULES

| Field | Content |
|-------|---------|
| **title** | Event/opportunity name only (e.g. "GOVATHON 2025"). No site name, no logo text. |
| **description** | REQUIRED. Full text: location, organizer, purpose, eligibility, dates. 500-2500 chars. Be thorough. |
| **funding_type** | grant | equity | debt | prize | other. Hackathons/competitions → prize. |
| **deadline** | YYYY-MM-DD. For "Du 1 oct 2025 au 30 avr 2026" use end date: 2026-04-30. French months: janv, févr, mars, avr, mai, juin, juil, août, sept, oct, nov, déc. |
| **funding_min/max** | Number. 10k→10000, 1M→1000000. null if unknown. |

## RULES

- All strings: plain text, no HTML entities in output
- Return ONLY the JSON object, no markdown fences, no explanation

## Content to analyze

PROMPT . "\n{$truncated}";
    }
}
