<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Industry;
use App\Models\Opportunity;
use App\Models\Stage;
use App\Models\Startup;
use App\Models\User;
use App\Services\OpportunityMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_matches_returns_sorted_by_score(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create([
            'user_id' => $user->id,
            'name' => 'Tech Startup',
            'industry' => 'tech',
            'stage' => 'seed',
            'country' => 'US',
        ]);

        $stageSeed = Stage::create(['name' => 'Seed', 'slug' => 'seed']);
        $industryTech = Industry::create(['name' => 'Technology', 'slug' => 'tech']);

        $oppHigh = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Tech Seed Grant',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);
        $oppHigh->stages()->attach($stageSeed->id);
        $oppHigh->industries()->attach($industryTech->id);

        $oppLow = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Other Grant',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);

        $service = app(OpportunityMatchingService::class);
        $matches = $service->calculateMatches($startup);

        $this->assertNotEmpty($matches);
        $scores = $matches->pluck('score')->toArray();
        $sorted = $scores;
        rsort($sorted);
        $this->assertEquals($sorted, $scores);
    }

    public function test_match_breakdown_includes_all_criteria(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create([
            'user_id' => $user->id,
            'name' => 'Startup',
            'industry' => 'fintech',
            'stage' => 'growth',
            'country' => 'NG',
        ]);

        $opp = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Grant',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);

        $service = app(OpportunityMatchingService::class);
        $matches = $service->calculateMatches($startup);

        $this->assertNotEmpty($matches);
        $breakdown = $matches->first()['breakdown'];
        $this->assertArrayHasKey('stage', $breakdown);
        $this->assertArrayHasKey('industry', $breakdown);
        $this->assertArrayHasKey('country', $breakdown);
        $this->assertArrayHasKey('revenue', $breakdown);
        $this->assertArrayHasKey('ownership', $breakdown);
    }
}
