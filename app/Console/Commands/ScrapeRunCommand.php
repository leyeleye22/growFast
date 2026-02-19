<?php



namespace App\Console\Commands;

use App\Jobs\ProcessScrapedEntryJob;
use App\Mail\ScrapingCompletedMail;
use App\Mail\ScrapingFailedMail;
use App\Mail\ScrapingStartedMail;
use App\Models\ScrapedEntry;
use App\Services\NotificationService;
use App\Services\Scraping\ScraperManager;
use Illuminate\Console\Command;

class ScrapeRunCommand extends Command
{
    protected $signature = 'scrape:run {--triggered-by=cron : api ou cron}';

    protected $description = 'Run scraping for all active opportunity sources';

    public function handle(ScraperManager $scraperManager, NotificationService $notificationService): int
    {
        $triggeredBy = $this->option('triggered-by');

        $this->info('Starting scrape run...');
        $notificationService->send(new ScrapingStartedMail($triggeredBy));

        try {
            $scraperManager->run();

            $unprocessed = ScrapedEntry::where('processed', false)->get();
            $count = $unprocessed->count();
            $unprocessed->each(fn ($entry) => ProcessScrapedEntryJob::dispatch($entry));

            $this->info('Scrape run completed.');
            $notificationService->send(new ScrapingCompletedMail(
                entriesFound: $count,
                jobsDispatched: $count,
                triggeredBy: $triggeredBy
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $notificationService->send(new ScrapingFailedMail(
                errorMessage: $e->getMessage(),
                sourceName: null
            ));
            throw $e;
        }
    }
}
