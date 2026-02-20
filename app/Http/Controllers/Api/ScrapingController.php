<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\services\AI\OpportunityExtractor;
use App\services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapingController extends Controller
{
    public function run(): JsonResponse
    {
        try {
            if (! request()->user()->can('run_scraper')) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            Log::info('[POST] ScrapingController@run');
            Artisan::call('scrape:run', ['--triggered-by' => 'api']);
            Log::info('Scrape run triggered');
            return response()->json(['message' => 'Scrape run completed']);
        } catch (Throwable $e) {
            Log::error('ScrapingController@run failed', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Extract opportunity data from URL or raw content via Gemini.
     * POST body: { url?: string, raw_content?: string }
     */
    public function extract(Request $request): JsonResponse
    {
        $url = trim((string) $request->input('url', ''));
        $rawContent = trim((string) $request->input('raw_content', ''));

        $content = '';
        $externalUrl = null;

        if ($url !== '') {
            $cacheBuster = str_contains($url, '?') ? '&_=' . time() : '?_=' . time();
            $response = Http::timeout(30)->get($url . $cacheBuster);
            if (! $response->successful()) {
                return response()->json(['message' => 'Unable to fetch URL.'], 400);
            }
            $content = $response->body();
            $externalUrl = $url;
        } elseif ($rawContent !== '') {
            $content = $rawContent;
        } else {
            return response()->json(['message' => 'Enter a URL or paste raw content.'], 400);
        }

        $extractor = app(OpportunityExtractor::class);
        $extracted = $extractor->extractForTest($content);

        $data = [
            'title' => $extracted['title'] ?? null,
            'description' => $extracted['description'] ?? null,
            'funding_type' => $extracted['funding_type'] ?? null,
            'deadline' => $extracted['deadline'] ?? null,
            'funding_min' => $extracted['funding_min'] ?? null,
            'funding_max' => $extracted['funding_max'] ?? null,
            'source' => $extracted['source'] ?? null,
        ];
        if ($externalUrl !== null) {
            $data['external_url'] = $externalUrl;
        }

        return response()->json($data);
    }

    /**
     * Fetch global: query naturelle → Gemini (Google Search) → URLs → scraping → opportunités.
     * POST body: { "query": "opportunités novembre tech 200000 dollars Sénégal" }
     */
    public function fetch(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('query', ''));
        if ($query === '') {
            return response()->json(['message' => 'Query is required.'], 400);
        }

        $gemini = app(GeminiService::class);
        $urls = $gemini->searchOpportunityUrls($query);

        if (empty($urls)) {
            return response()->json([
                'message' => 'No URLs found for this query.',
                'opportunities' => [],
            ]);
        }

        $extractor = app(OpportunityExtractor::class);
        $opportunities = [];

        foreach ($urls as $url) {
            try {
                $cacheBuster = str_contains($url, '?') ? '&_=' . time() : '?_=' . time();
                $response = Http::timeout(30)->get($url . $cacheBuster);
                if (! $response->successful()) {
                    continue;
                }
                $content = $response->body();
                $extracted = $extractor->extractForTest($content);
                if (! empty($extracted['title'])) {
                    $opportunities[] = array_merge($extracted, ['external_url' => $url]);
                }
            } catch (Throwable $e) {
                Log::warning('Fetch URL failed', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'urls_found' => count($urls),
            'opportunities' => $opportunities,
        ]);
    }
}
