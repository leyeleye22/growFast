<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_authenticated_user_can_get_matches_for_own_startup(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create([
            'user_id' => $user->id,
            'name' => 'Test Startup',
            'industry' => 'tech',
            'stage' => 'seed',
            'country' => 'US',
        ]);
        Opportunity::withoutGlobalScopes()->create([
            'title' => 'Tech Grant',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/startups/' . $startup->id . '/matches');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    public function test_authenticated_user_cannot_get_matches_for_other_startup(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $startup = Startup::create([
            'user_id' => $other->id,
            'name' => 'Other Startup',
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/startups/' . $startup->id . '/matches');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_get_matches(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create(['user_id' => $user->id, 'name' => 'Startup']);

        $response = $this->getJson('/api/startups/' . $startup->id . '/matches');
        $response->assertStatus(401);
    }
}
