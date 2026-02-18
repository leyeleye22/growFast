<?php

declare(strict_types=1);

namespace App\Services\Scraping;

use App\Enums\ScrapingRunStatus;
use App\Models\OpportunitySource;
use App\Models\ScrapingRun;
use App\Services\Scraping\Strategies\DefaultScraperStrategy;
use Illuminate\Support\Str;

class ScraperManager
{
    protected array $strategies = [
        'default' => DefaultScraperStrategy::class,
    ];

    public function run(): void
    {
        $sources = OpportunitySource::where('active', true)->get();

        foreach ($sources as $source) {
            $this->runForSource($source);
        }
    }

    public function runForSource(OpportunitySource $source): ScrapingRun
    {
        $run = ScrapingRun::create([
            'source_id' => $source->id,
            'status' => ScrapingRunStatus::Running,
            'items_found' => 0,
        ]);

        try {
            $scraper = $this->resolveScraper($source, $run);
            $count = $scraper->scrape();

            $run->update([
                'status' => ScrapingRunStatus::Completed,
                'items_found' => $count,
            ]);
        } catch (\Throwable $e) {
            $run->update(['status' => ScrapingRunStatus::Failed]);
            throw $e;
        }

        return $run;
    }

    protected function resolveScraper(OpportunitySource $source, ScrapingRun $run): AbstractScraper
    {
        $strategy = $source->scraping_strategy ?: 'default';
        $class = $this->strategies[$strategy] ?? $this->strategies['default'];

        return new $class($source, $run);
    }

    public function registerStrategy(string $name, string $class): void
    {
        $this->strategies[$name] = $class;
    }
}
