<?php

declare(strict_types=1);

namespace App\Services\Scraping;

use App\Models\OpportunitySource;
use App\Models\ScrapedEntry;
use App\Models\ScrapingRun;
use Illuminate\Support\Facades\Http;

abstract class AbstractScraper
{
    protected OpportunitySource $source;

    protected ScrapingRun $run;

    public function __construct(OpportunitySource $source, ScrapingRun $run)
    {
        $this->source = $source;
        $this->run = $run;
    }

    abstract public function scrape(): int;

    protected function fetchUrl(string $url): ?string
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            return null;
        }

        return $response->body();
    }

    protected function createOrSkipEntry(string $externalUrl, string $rawContent): ?ScrapedEntry
    {
        $hash = hash('sha256', $externalUrl . $rawContent);

        $exists = ScrapedEntry::where('content_hash', $hash)->exists();

        if ($exists) {
            return null;
        }

        return ScrapedEntry::create([
            'scraping_run_id' => $this->run->id,
            'external_url' => $externalUrl,
            'raw_content' => $rawContent,
            'content_hash' => $hash,
            'processed' => false,
            'duplicate_detected' => false,
        ]);
    }
}
