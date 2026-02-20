<?php



namespace App\Jobs;

use App\Enums\OpportunityStatus;
use App\Models\Opportunity;
use App\Models\ScrapedEntry;
use App\services\AI\OpportunityExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScrapedEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ScrapedEntry $scrapedEntry
    ) {}

    public function handle(OpportunityExtractor $extractor): void
    {
        $extracted = $extractor->extract($this->scrapedEntry->raw_content ?? '');

        if ($extracted) {
            Opportunity::create([
                'title' => $extracted['title'],
                'description' => $this->scrapedEntry->raw_content,
                'funding_type' => $extracted['funding_type'],
                'deadline' => $extracted['deadline'],
                'funding_min' => $extracted['funding_min'],
                'funding_max' => $extracted['funding_max'],
                'status' => OpportunityStatus::Pending,
                'external_url' => $this->scrapedEntry->external_url,
                'source' => 'scraper',
            ]);
        }

        $this->scrapedEntry->update(['processed' => true]);
    }
}
