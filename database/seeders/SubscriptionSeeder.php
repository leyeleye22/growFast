<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        Subscription::firstOrCreate(
            ['slug' => 'free'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Free',
                'description' => 'Free tier with basic access',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'currency' => 'USD',
                'features' => ['basic_opportunities'],
                'is_active' => true,
            ]
        );

        Subscription::firstOrCreate(
            ['slug' => 'premium'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Premium',
                'description' => 'Premium tier with full access',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'currency' => 'USD',
                'features' => ['basic_opportunities', 'premium_opportunities', 'matching'],
                'is_active' => true,
            ]
        );
    }
}
