<?php

declare(strict_types=1);

namespace App\Services\Scraping\Strategies;

use App\Models\OpportunitySource;
use App\Models\ScrapingRun;
use App\Services\Scraping\AbstractScraper;
use Illuminate\Support\Facades\Http;

class DefaultScraperStrategy extends AbstractScraper
{
    public function scrape(): int
    {
        $count = 0;
        $response = Http::timeout(30)->get($this->source->base_url);

        if (!$response->successful()) {
            return 0;
        }

        $urls = $this->extractUrls($response->body());

        foreach (array_slice($urls, 0, 10) as $url) {
            $content = $this->fetchUrl($url);
            if ($content && $this->createOrSkipEntry($url, $content)) {
                $count++;
            }
        }

        return $count;
    }

    protected function extractUrls(string $html): array
    {
        preg_match_all('/href=["\']([^"\']+)["\']/', $html, $matches);

        return array_values(array_unique(array_filter($matches[1] ?? [], function (string $url): bool {
            return str_starts_with($url, 'http') && str_contains($url, parse_url($this->source->base_url, PHP_URL_HOST));
        })));
    }
}
