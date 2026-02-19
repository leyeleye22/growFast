<?php



namespace Tests\Feature;

use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_authenticated_user_can_list_startups(): void
    {
        $user = User::factory()->create();
        Startup::create(['user_id' => $user->id, 'name' => 'My Startup']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/startups');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_authenticated_user_can_create_startup(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/startups', [
                'name' => 'New Startup',
                'tagline' => 'We innovate',
                'industry' => 'tech',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Startup']);
        $this->assertDatabaseHas('startups', ['name' => 'New Startup']);
    }

    public function test_authenticated_user_can_show_own_startup(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create(['user_id' => $user->id, 'name' => 'My Startup']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/startups/' . $startup->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'My Startup']);
    }

    public function test_authenticated_user_cannot_show_other_startup(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $startup = Startup::create(['user_id' => $other->id, 'name' => 'Other Startup']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/startups/' . $startup->id);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_update_own_startup(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create(['user_id' => $user->id, 'name' => 'Old Name']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/startups/' . $startup->id, ['name' => 'Updated Name']);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_authenticated_user_can_delete_own_startup(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create(['user_id' => $user->id, 'name' => 'To Delete']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/startups/' . $startup->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('startups', ['id' => $startup->id]);
    }

    public function test_unauthenticated_user_cannot_access_startups(): void
    {
        $response = $this->getJson('/api/startups');
        $response->assertStatus(401);
    }
}
