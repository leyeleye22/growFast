<?php



namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\SubscriptionSeeder::class);
    }

    public function test_authenticated_user_can_subscribe(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::where('slug', 'premium')->first();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user-subscriptions/subscribe', [
                'subscription_id' => $subscription->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'subscription_id', 'status']);
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    public function test_authenticated_user_can_cancel_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::where('slug', 'premium')->first();
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => SubscriptionStatus::Active,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user-subscriptions/cancel');

        $response->assertStatus(200);
    }

    public function test_cancel_returns_404_when_no_active_subscription(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user-subscriptions/cancel');

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_subscribe(): void
    {
        $subscription = Subscription::where('slug', 'premium')->first();
        $response = $this->postJson('/api/user-subscriptions/subscribe', [
            'subscription_id' => $subscription->id,
        ]);
        $response->assertStatus(401);
    }
}
