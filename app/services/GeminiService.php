<?php



namespace App\services;

use App\Models\Opportunity;
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

    /**
     * Answer a user question about an opportunity using Gemini.
     */
    public function askAboutOpportunity(Opportunity $opportunity, string $question): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $context = [
            'title' => $opportunity->title,
            'description' => $opportunity->description,
            'funding_type' => $opportunity->funding_type,
            'deadline' => $opportunity->deadline?->format('Y-m-d'),
            'funding_min' => $opportunity->funding_min,
            'funding_max' => $opportunity->funding_max,
            'external_url' => $opportunity->external_url,
            'eligibility_criteria' => $opportunity->eligibility_criteria,
        ];

        $contextStr = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are an assistant helping users understand funding opportunities. You have the following opportunity data:

{$contextStr}

The user asks: {$question}

Answer concisely and helpfully based only on the opportunity data above. If the information is not in the data, say so. Be clear and practical.
PROMPT;

        return $this->generateContent($prompt, [
            'temperature' => 0.7,
            'maxOutputTokens' => 1024,
        ]);
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

        $urls = array_values(array_unique(array_slice($urls, 0, 20)));
        shuffle($urls);

        return $urls;
    }

    /**
     * Use Gemini to expand short/vague queries into better search terms.
     * Respects query intent: only add hackathon if user asked for it.
     */
    private function expandQueryWithGemini(string $query): ?string
    {
        if (str_word_count($query) >= 6) {
            return null;
        }

        $prompt = <<<PROMPT
Rewrite this search query for finding funding opportunities into a more effective web search. Keep it concise (5-12 words). Fix typos.

RULES:
- If the user says "opportunities" or "grants" or a country (Ghana, Senegal, etc.): focus on grants, funding, accelerators, NOT hackathons.
- Only add "hackathon" if the user explicitly asks for hackathons.
- Preserve country/region and sector (tech, health, etc.) from the original query.
- Output: Only the rewritten query, nothing else. No quotes, no explanation.

Input: "{$query}"
Output:
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
     * For "opportunities" + country: grants, funding. NO hackathon by default.
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

        $countryUrls = $this->getCountrySpecificUrls($lower);
        if (! empty($countryUrls)) {
            return $countryUrls;
        }

        if (str_contains($lower, 'grant') || str_contains($lower, 'subvention')) {
            return [
                'https://www.f6s.com/opportunities',
                'https://www.fundsforngos.org/',
                'https://www.grantwatch.com/',
                'https://opportunitydesk.org/',
                'https://vc4a.com/opportunities/',
            ];
        }

        $base = [
            'https://www.f6s.com/opportunities',
            'https://vc4a.com/opportunities/',
            'https://opportunitydesk.org/',
            'https://www.africanbusinesscentral.com/grants/',
            'https://www.fundsforngos.org/',
            'https://www.grantwatch.com/',
            'https://www.crunchbase.com/organizations',
        ];
        $q = urlencode($query);
        return array_merge($base, [
            'https://www.f6s.com/opportunities?q=' . $q,
            'https://opportunitydesk.org/?s=' . $q,
        ]);
    }

    /**
     * Country-specific URLs for grants and funding (no hackathons).
     *
     * @return array<string>
     */
    private function getCountrySpecificUrls(string $query): array
    {
        $base = [
            'https://www.f6s.com/opportunities',
            'https://vc4a.com/opportunities/',
            'https://opportunitydesk.org/',
        ];

        if (str_contains($query, 'ghana')) {
            return array_merge($base, [
                'https://www.africanbusinesscentral.com/grants/ghana/',
                'https://www.fundsforngos.org/africa/ghana/',
                'https://www.grantwatch.com/cat/12/ghana-grants.html',
            ]);
        }
        if (str_contains($query, 'senegal') || str_contains($query, 'sénégal')) {
            return array_merge($base, [
                'https://www.africanbusinesscentral.com/grants/senegal/',
                'https://www.fundsforngos.org/africa/senegal/',
            ]);
        }
        if (str_contains($query, 'nigeria')) {
            return array_merge($base, [
                'https://www.africanbusinesscentral.com/grants/nigeria/',
                'https://www.fundsforngos.org/africa/nigeria/',
            ]);
        }
        if (str_contains($query, 'africa') || str_contains($query, 'afrique')) {
            return array_merge($base, [
                'https://www.africanbusinesscentral.com/grants/',
                'https://www.fundsforngos.org/africa/',
                'https://www.youngafricanleaders.gov/',
            ]);
        }

        return [];
    }

    /**
     * Try Google Search grounding first.
     *
     * @return array<string>
     */
    private function searchWithGrounding(string $query): array
    {
        $prompt = <<<PROMPT
Find web page URLs that list REAL funding opportunities for: {$query}

CRITICAL RULES:
- For "opportunities" + country (e.g. Ghana, Senegal): prioritize grants, accelerators, government funding, VC4A, F6S, country-specific portals. NO generic hackathon listings unless the user asked for hackathons.
- For "tech" + country: tech grants, tech accelerators, innovation funds in that country.
- Exclude: generic pitch competitions, hackathon aggregators (Devpost, hackathon.com) unless the query explicitly asks for hackathons.
- Prefer: pages that list actual opportunities with deadlines and amounts, not marketing or event listings.
- Return 10-15 valid https URLs as JSON array. VARY the results: include different aggregators, country-specific pages, individual opportunity pages - NOT always the same 2-3 sites.
PROMPT;

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'tools' => [['google_search' => (object) []]],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(60)
            ->post($this->buildUrl(), $payload);

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
You know funding opportunity websites. Return a JSON array of URLs matching: {$query}

RULES:
- For "opportunities" + country (Ghana, Senegal, Nigeria, etc.): VC4A, F6S, fundsforngos, country government grant portals, African Business Central, opportunity aggregators. NO hackathon sites unless the user asked for hackathons.
- For tech + country: tech grants, accelerators, innovation funds.
- Exclude: Devpost, hackathon.com, MLH, Eventbrite hackathons unless the query explicitly mentions hackathons.
- Return 8-15 valid https URLs as JSON array. VARY the sources - different sites, not always the same 2.
PROMPT;

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.7,
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

        $truncated = strlen($rawContent) > 15000 ? substr($rawContent, 0, 15000) . '...[truncated]' : $rawContent;

        $prompt = <<<PROMPT
You are given raw HTML or text from a webpage about a funding opportunity. Extract and return ONLY the main content.

Remove: navigation, ads, scripts, footers, cookie banners.

Keep EVERYTHING relevant: full description, what the opportunity is, who organizes it, who can apply, eligibility criteria, funding amounts, deadlines, how to apply, requirements. Do NOT truncate.

Return ONLY the cleaned content as plain text. No explanations. Preserve all important details.
PROMPT;

        $prompt .= "\n\nContent to preprocess:\n{$truncated}";

        return $this->generateContent($prompt, [
            'temperature' => 0.1,
            'maxOutputTokens' => 8192,
        ]);
    }

    /**
     * Filter out opportunities that don't match the user query.
     * For "opportunities tech Ghana": remove hackathons, pitch competitions.
     * For "hackathon": keep hackathon-related only.
     *
     * @param  array<int, array<string, mixed>>  $opportunities
     * @return array<int, array<string, mixed>>
     */
    public function filterRelevantOpportunities(string $query, array $opportunities): array
    {
        if (empty($opportunities) || count($opportunities) <= 2) {
            return $opportunities;
        }

        $lower = strtolower($query);
        $wantsHackathon = str_contains($lower, 'hackathon');
        $wantsGrantsOrOpportunities = str_contains($lower, 'grant') || str_contains($lower, 'opportunit')
            || str_contains($lower, 'subvention') || preg_match('/\b(tech|ghana|senegal|nigeria|africa)\b/', $lower);

        $filtered = [];
        foreach ($opportunities as $opp) {
            $title = strtolower($opp['title'] ?? '');
            $desc = strtolower($opp['description'] ?? '');

            if ($wantsHackathon) {
                $filtered[] = $opp;
                continue;
            }

            if ($wantsGrantsOrOpportunities) {
                if (str_contains($title, 'hackathon') && ! str_contains($desc, 'grant') && ! str_contains($desc, 'funding')) {
                    continue;
                }
                if (str_contains($title, 'pitch') && ! str_contains($desc, 'grant') && ! str_contains($desc, 'funding') && ! str_contains($desc, 'prize')) {
                    continue;
                }
            }

            $filtered[] = $opp;
        }

        return ! empty($filtered) ? $filtered : $opportunities;
    }

    /**
     * Clean scraped opportunity data via Gemini: decode entities, fix formatting, normalize.
     *
     * @param  array<int, array<string, mixed>>  $opportunities
     * @return array<int, array<string, mixed>>
     */
    public function cleanOpportunities(array $opportunities): array
    {
        if (! $this->isConfigured() || empty($opportunities)) {
            return $opportunities;
        }

        $json = json_encode($opportunities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (strlen($json) > 28000) {
            $json = substr($json, 0, 28000) . '...[truncated]';
        }

        $prompt = <<<'PROMPT'
You receive scraped funding opportunity data. Clean and normalize it. Return a JSON array of objects with the same structure.

## CLEANING RULES

1. **title**: Decode HTML entities (&#8211;→-, &amp;→&, &#039;→'). Remove site names (e.g. "SiteName – "). Keep only the opportunity name. Plain text.
2. **description**: Decode HTML entities. MINIMUM 10 lines (500+ chars). Include: what it is, organizer, eligibility, benefits, dates, how to apply. If vague or truncated, expand from context. Never output less than 10 lines.
3. **funding_type**: Must be exactly: grant | equity | debt | prize | other. Normalize variations.
4. **deadline**: YYYY-MM-DD format. Parse French dates. null if absent or invalid.
5. **funding_min / funding_max**: Numbers only. 10k→10000, 1M→1000000. null if unknown.
6. **external_url**: Keep unchanged.
7. **source**: Keep unchanged.

Return ONLY the JSON array. No markdown, no explanation. Same number of objects as input.

## Input data

PROMPT;

        $prompt .= "\n\n{$json}";

        $text = $this->generateContent($prompt, [
            'temperature' => 0.1,
            'maxOutputTokens' => 8192,
            'generationConfig' => ['responseMimeType' => 'application/json'],
        ]);

        if (! $text) {
            return $opportunities;
        }

        $decoded = json_decode(trim($text), true);
        if (! is_array($decoded)) {
            return $opportunities;
        }

        $cleaned = [];
        foreach ($decoded as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $original = $opportunities[$i] ?? [];
            $cleaned[] = array_merge($original, [
                'title' => $item['title'] ?? $original['title'] ?? null,
                'description' => $item['description'] ?? $original['description'] ?? null,
                'funding_type' => $item['funding_type'] ?? $original['funding_type'] ?? null,
                'deadline' => $item['deadline'] ?? $original['deadline'] ?? null,
                'funding_min' => $item['funding_min'] ?? $original['funding_min'] ?? null,
                'funding_max' => $item['funding_max'] ?? $original['funding_max'] ?? null,
            ]);
        }

        return ! empty($cleaned) ? $cleaned : $opportunities;
    }

    public function extractOpportunityFromContent(string $rawContent): ?array
    {
        $cleaned = $this->preprocessRawContent($rawContent);
        $contentToExtract = $this->preprocessContent($cleaned) ?? $cleaned;

        $prompt = $this->buildOpportunityExtractionPrompt($contentToExtract);
        $text = $this->generateContent($prompt, [
            'temperature' => 0.2,
            'maxOutputTokens' => 8192,
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
                        'description' => 'REQUIRED. MINIMUM 10 lines (500+ chars). Include: what it is, organizer, who can apply, eligibility, benefits, dates, how to apply. Never truncate. Be specific.',
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
                    'country' => [
                        'type' => 'STRING',
                        'description' => 'ISO 3166-1 alpha-2 code (e.g. GH, SN, NG, US, FR) or country name. null if unknown.',
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
        $truncated = strlen($rawContent) > 18000 ? substr($rawContent, 0, 18000) . '...[truncated]' : $rawContent;

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
  "country": "string | null",
  "funding_min": number | null,
  "funding_max": number | null
}
```

## FIELD RULES

| Field | Content |
|-------|---------|
| **title** | Event/opportunity name only (e.g. "GOVATHON 2025"). No site name, no logo text. |
| **description** | REQUIRED. MIN 10 lines (500+ chars). Include: what it is, organizer, eligibility, benefits, dates, how to apply. Never truncate. |
| **funding_type** | grant | equity | debt | prize | other. Hackathons/competitions → prize. |
| **deadline** | YYYY-MM-DD. For "Du 1 oct 2025 au 30 avr 2026" use end date: 2026-04-30. French months: janv, févr, mars, avr, mai, juin, juil, août, sept, oct, nov, déc. |
| **funding_min/max** | Number. 10k→10000, 1M→1000000. null if unknown. |
| **country** | ISO code (GH, SN, NG, US, FR) or country name. null if unknown. |

## RULES

- All strings: plain text, no HTML entities in output
- Return ONLY the JSON object, no markdown fences, no explanation

## Content to analyze

PROMPT . "\n{$truncated}";
    }
}
