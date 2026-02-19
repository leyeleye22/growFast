<?php



namespace Tests\Feature;

use App\Models\OpportunitySuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunitySuggestionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_submit_opportunity_suggestion(): void
    {
        $response = $this->postJson('/api/opportunity-suggestions', [
            'grant_name' => 'Community Grant',
            'award_amount_min' => 5000,
            'award_amount_max' => 25000,
            'application_link' => 'https://example.com/apply',
            'deadline' => now()->addMonths(2)->format('Y-m-d'),
            'description' => 'A great grant for startups',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['grant_name' => 'Community Grant']);
        $this->assertDatabaseHas('opportunity_suggestions', ['grant_name' => 'Community Grant']);
    }

    public function test_authenticated_user_suggestion_stores_user_id(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/opportunity-suggestions', [
                'grant_name' => 'User Suggested Grant',
            ]);

        $response->assertStatus(201);
        $suggestion = OpportunitySuggestion::where('grant_name', 'User Suggested Grant')->first();
        $this->assertEquals($user->id, $suggestion->user_id);
    }

    public function test_suggestion_requires_grant_name(): void
    {
        $response = $this->postJson('/api/opportunity-suggestions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['grant_name']);
    }
}
