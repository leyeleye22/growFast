<?php

namespace App\Jobs;

use App\Enums\OpportunityStatus;
use App\Models\Opportunity;
use App\Models\ScrapedEntry;
use App\services\AI\OpportunityExtractor;
use App\services\Scraping\OpportunityDataMapper;
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

    public function handle(OpportunityExtractor $extractor, OpportunityDataMapper $mapper): void
    {
        $extracted = $extractor->extract($this->scrapedEntry->raw_content ?? '');

        if ($extracted) {
            $extracted['external_url'] = $this->scrapedEntry->external_url;
            $extracted['source'] = 'scraper';

            $attributes = $mapper->toOpportunityAttributes($extracted);
            $attributes['status'] = OpportunityStatus::Pending;

            $opportunity = Opportunity::create($attributes);

            $industryIds = $mapper->resolveIndustryIds($extracted['industry'] ?? null);
            if (! empty($industryIds)) {
                $opportunity->industries()->sync($industryIds);
            }

            $stageIds = $mapper->resolveStageIds($extracted['stage'] ?? null);
            if (! empty($stageIds)) {
                $opportunity->stages()->sync($stageIds);
            }

            $countryCodes = $mapper->resolveCountryCodes($extracted['country'] ?? null);
            if (! empty($countryCodes)) {
                foreach ($countryCodes as $code) {
                    $opportunity->countryCodes()->create(['country_code' => $code]);
                }
            }
        }

        $this->scrapedEntry->update(['processed' => true]);
    }
}
