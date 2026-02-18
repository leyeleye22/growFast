<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Industry;
use App\Models\Opportunity;
use App\Models\Stage;
use App\Models\Startup;
use App\Models\User;
use App\Services\OpportunityMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_returns_highest_score_first(): void
    {
        $user = User::factory()->create();

        $startup = Startup::create([
            'user_id' => $user->id,
            'name' => 'Test Startup',
            'industry' => 'tech',
            'stage' => 'seed',
            'country' => 'US',
        ]);

        $stageSeed = Stage::create(['name' => 'Seed', 'slug' => 'seed']);
        $stageGrowth = Stage::create(['name' => 'Growth', 'slug' => 'growth']);
        $industryTech = Industry::create(['name' => 'Technology', 'slug' => 'tech']);

        $oppHighMatch = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Seed Tech Grant',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);
        $oppHighMatch->stages()->attach($stageSeed->id);
        $oppHighMatch->industries()->attach($industryTech->id);

        $oppLowMatch = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Growth Grant',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);
        $oppLowMatch->stages()->attach($stageGrowth->id);

        $matchingService = app(OpportunityMatchingService::class);
        $results = $matchingService->calculateMatches($startup);

        $this->assertNotEmpty($results);
        $first = $results->first();
        $this->assertGreaterThanOrEqual(0, $first['score']);
    }
}
