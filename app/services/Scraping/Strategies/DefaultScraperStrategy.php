<?php

namespace App\services\Scraping\Strategies;

use App\services\Scraping\AbstractScraper;
use App\services\Scraping\ContentSanitizer;
use Illuminate\Support\Facades\Http;

class DefaultScraperStrategy extends AbstractScraper
{
    /** URL path patterns that typically indicate article/opportunity pages (not search, category, or listing). */
    protected array $articlePathPatterns = [
        '/\/grant\//',
        '/\/funding\//',
        '/\/opportunity\//',
        '/\/program\//',
        '/\/[0-9]{4}\/[0-9]{2}\//',  // date-based slugs
        '/\/[a-z0-9-]+-\d{4,}\//',   // slug with id
        '/\/[a-z0-9-]{20,}\//',      // long descriptive slugs
    ];

    /** Path patterns to exclude (search, category, tag pages). */
    protected array $excludePathPatterns = [
        '/\/search/',
        '/\/category\//',
        '/\/tag\//',
        '/\/page\/\d+/',
        '/\?.*(?:s=|q=|search=)/',
    ];

    public function scrape(): int
    {
        $count = 0;
        $response = Http::timeout(30)->get($this->source->base_url);

        if (! $response->successful()) {
            return 0;
        }

        $urls = $this->extractArticleUrls($response->body());
        $sanitizer = app(ContentSanitizer::class);

        foreach (array_slice($urls, 0, 15) as $url) {
            if ($sanitizer->isBlacklisted($url)) {
                continue;
            }
            $content = $this->fetchUrl($url);
            if ($content && $this->createOrSkipEntry($url, $content)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Extract article/opportunity URLs from listing page. Prefer individual opportunity links over search results.
     */
    protected function extractArticleUrls(string $html): array
    {
        $baseHost = parse_url($this->source->base_url, PHP_URL_HOST) ?? '';
        preg_match_all('/href=["\']([^"\']+)["\']/', $html, $matches);

        $urls = array_values(array_unique(array_filter($matches[1] ?? [], function (string $url) use ($baseHost): bool {
            if (! str_starts_with($url, 'http')) {
                return false;
            }
            $host = parse_url($url, PHP_URL_HOST);
            if (! $host || ! str_contains($host, $baseHost)) {
                return false;
            }
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            foreach ($this->excludePathPatterns as $pattern) {
                if (preg_match($pattern, $path . (parse_url($url, PHP_URL_QUERY) ?? ''))) {
                    return false;
                }
            }
            foreach ($this->articlePathPatterns as $pattern) {
                if (preg_match($pattern, $path)) {
                    return true;
                }
            }
            return strlen($path) > 15 && ! str_ends_with($path, '/');
        })));

        if (empty($urls)) {
            return $this->extractUrlsFallback($html);
        }

        return $urls;
    }

    /** Fallback: any internal links when no article patterns match. */
    protected function extractUrlsFallback(string $html): array
    {
        $baseHost = parse_url($this->source->base_url, PHP_URL_HOST) ?? '';
        preg_match_all('/href=["\']([^"\']+)["\']/', $html, $matches);

        return array_values(array_unique(array_filter($matches[1] ?? [], function (string $url) use ($baseHost): bool {
            if (! str_starts_with($url, 'http')) {
                return false;
            }
            $host = parse_url($url, PHP_URL_HOST);
            return $host && str_contains($host, $baseHost);
        })));
    }
}
