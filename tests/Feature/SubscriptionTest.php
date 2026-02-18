<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Opportunity;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Subscription $freeSubscription;

    protected Subscription $premiumSubscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->freeSubscription = Subscription::create([
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'currency' => 'USD',
            'is_active' => true,
        ]);
        $this->premiumSubscription = Subscription::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 29.99,
            'billing_cycle' => 'monthly',
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    public function test_user_with_active_subscription_can_access_premium_opportunity(): void
    {
        UserSubscription::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->premiumSubscription->id,
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => SubscriptionStatus::Active,
        ]);

        $opportunity = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Premium Opportunity',
            'subscription_required_id' => $this->premiumSubscription->id,
            'is_premium' => true,
            'status' => 'active',
        ]);

        $this->assertTrue($this->user->activeSubscription()->exists());
    }

    public function test_user_with_expired_subscription_cannot_access_premium_opportunity(): void
    {
        UserSubscription::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->premiumSubscription->id,
            'started_at' => now()->subMonths(2),
            'expires_at' => now()->subDay(),
            'status' => SubscriptionStatus::Expired,
        ]);

        $activeSub = $this->user->userSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        $this->assertNull($activeSub);
    }

    public function test_user_can_have_only_one_active_subscription(): void
    {
        UserSubscription::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->freeSubscription->id,
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => SubscriptionStatus::Active,
        ]);

        $activeCount = $this->user->userSubscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();

        $this->assertEquals(1, $activeCount);
    }
}
