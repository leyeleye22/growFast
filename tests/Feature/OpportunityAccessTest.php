<?php



namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;
use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_opportunity_accessible_without_subscription(): void
    {
        $user = User::factory()->create();

        $opportunity = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Free Opportunity',
            'is_premium' => false,
            'subscription_required_id' => null,
            'status' => 'active',
        ]);

        $policy = new \App\Policies\OpportunityPolicy();
        $this->assertTrue($policy->view($user, $opportunity));
    }

    public function test_premium_opportunity_requires_active_subscription(): void
    {
        $user = User::factory()->create();
        $premium = Subscription::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'price' => 29.99,
            'billing_cycle' => 'monthly',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $opportunity = Opportunity::withoutGlobalScopes()->create([
            'title' => 'Premium Opportunity',
            'is_premium' => true,
            'subscription_required_id' => $premium->id,
            'status' => 'active',
        ]);

        $policy = new \App\Policies\OpportunityPolicy();

        $this->assertFalse($policy->view($user, $opportunity));

        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_id' => $premium->id,
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => SubscriptionStatus::Active,
        ]);

        $this->assertTrue($policy->view($user, $opportunity));
    }
}
