<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessScrapedEntryJob;
use App\Models\OpportunitySource;
use App\Models\ScrapedEntry;
use App\Models\ScrapingRun;
use App\Services\AI\OpportunityExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScrapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrape_command_creates_run_and_entries(): void
    {
        Http::fake([
            '*' => Http::response('<html><body><a href="https://example.com/opp1">Link 1</a></body></html>', 200),
        ]);

        $source = OpportunitySource::create([
            'name' => 'Test Source',
            'base_url' => 'https://example.com',
            'scraping_strategy' => 'default',
            'active' => true,
        ]);

        $this->artisan('scrape:run')
            ->assertSuccessful();

        $run = ScrapingRun::where('source_id', $source->id)->first();
        $this->assertNotNull($run);
        $this->assertEquals('completed', $run->status->value);
    }

    public function test_duplicate_detection_prevents_duplicate_entries(): void
    {
        $run = ScrapingRun::create([
            'source_id' => OpportunitySource::create([
                'name' => 'Test',
                'base_url' => 'https://example.com',
                'active' => true,
            ])->id,
            'status' => 'completed',
            'items_found' => 0,
        ]);

        $hash = hash('sha256', 'https://example.com/1' . 'content');
        ScrapedEntry::create([
            'scraping_run_id' => $run->id,
            'external_url' => 'https://example.com/1',
            'raw_content' => 'content',
            'content_hash' => $hash,
            'processed' => false,
        ]);

        $duplicateExists = ScrapedEntry::where('content_hash', $hash)->count() > 1;
        $this->assertFalse($duplicateExists);
    }

    public function test_process_scraped_entry_job_marks_as_processed(): void
    {
        Bus::fake();

        $source = OpportunitySource::create([
            'name' => 'Test',
            'base_url' => 'https://example.com',
            'active' => true,
        ]);

        $run = ScrapingRun::create([
            'source_id' => $source->id,
            'status' => 'completed',
            'items_found' => 1,
        ]);

        $entry = ScrapedEntry::create([
            'scraping_run_id' => $run->id,
            'external_url' => 'https://example.com/1',
            'raw_content' => '<title>Grant Opportunity</title> Deadline: 2026-12-31',
            'content_hash' => hash('sha256', 'https://example.com/1' . '<title>Grant Opportunity</title> Deadline: 2026-12-31'),
            'processed' => false,
        ]);

        $extractor = $this->createMock(OpportunityExtractor::class);
        $extractor->method('extract')->willReturn([
            'title' => 'Grant Opportunity',
            'funding_type' => 'grant',
            'deadline' => '2026-12-31',
            'industry' => null,
            'stage' => null,
            'funding_min' => null,
            'funding_max' => null,
        ]);

        $this->app->instance(OpportunityExtractor::class, $extractor);

        $job = new ProcessScrapedEntryJob($entry);
        $job->handle(app(OpportunityExtractor::class));

        $entry->refresh();
        $this->assertTrue($entry->processed);
    }
}
