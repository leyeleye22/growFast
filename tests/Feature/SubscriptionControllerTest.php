<?php



namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\SubscriptionSeeder::class);
    }

    public function test_authenticated_user_can_list_subscriptions(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/subscriptions');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json());
    }

    public function test_authenticated_user_can_get_my_subscription(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/subscriptions/my');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_subscriptions(): void
    {
        $response = $this->getJson('/api/subscriptions');
        $response->assertStatus(401);
    }
}
