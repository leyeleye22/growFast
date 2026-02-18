<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessScrapedEntryJob;
use App\Services\Scraping\ScraperManager;
use Illuminate\Console\Command;

class ScrapeRunCommand extends Command
{
    protected $signature = 'scrape:run';

    protected $description = 'Run scraping for all active opportunity sources';

    public function handle(ScraperManager $scraperManager): int
    {
        $this->info('Starting scrape run...');

        $scraperManager->run();

        \App\Models\ScrapedEntry::where('processed', false)
            ->each(fn ($entry) => ProcessScrapedEntryJob::dispatch($entry));

        $this->info('Scrape run completed.');

        return self::SUCCESS;
    }
}
