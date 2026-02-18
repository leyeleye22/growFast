<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_authenticated_user_can_list_opportunities(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::findByName('startup', 'api'));
        $token = auth('api')->login($user);

        Opportunity::withoutGlobalScopes()->create([
            'title' => 'Test Grant',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/opportunities');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page']);
    }

    public function test_authenticated_user_can_show_opportunity(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::findByName('startup', 'api'));
        $token = auth('api')->login($user);

        $opp = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Free Grant',
            'status' => 'active',
            'is_premium' => false,
            'subscription_required_id' => null,
            'deadline' => now()->addMonth(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/opportunities/' . $opp->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Free Grant']);
    }

    public function test_admin_can_create_opportunity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findByName('admin', 'api'));
        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/opportunities', [
                'title' => 'New Grant',
                'funding_type' => 'grant',
                'deadline' => now()->addMonth()->format('Y-m-d'),
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'New Grant']);
        $this->assertDatabaseHas('opportunities', ['title' => 'New Grant']);
    }

    public function test_non_admin_cannot_create_opportunity(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::findByName('startup', 'api'));
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/opportunities', [
                'title' => 'New Grant',
                'funding_type' => 'grant',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_opportunity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findByName('admin', 'api'));
        $token = auth('api')->login($admin);

        $opp = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Old Title',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/opportunities/' . $opp->id, ['title' => 'Updated Title']);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    public function test_admin_can_delete_opportunity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findByName('admin', 'api'));
        $token = auth('api')->login($admin);

        $opp = Opportunity::withoutGlobalScopes()->create([
            'title' => 'To Delete',
            'status' => 'active',
            'deadline' => now()->addMonth(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/opportunities/' . $opp->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('opportunities', ['id' => $opp->id]);
    }

    public function test_unauthenticated_user_cannot_access_opportunities(): void
    {
        $response = $this->getJson('/api/opportunities');
        $response->assertStatus(401);
    }
}
