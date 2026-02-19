<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Opportunity;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AdminDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class, SubscriptionSeeder::class, IndustrySeeder::class, StageSeeder::class]);

        $admin = User::factory()->create([
            'name' => 'Admin GrowFast',
            'email' => 'admin@growfast.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole(Role::findByName('admin', 'web'));

        $freeSub = Subscription::where('slug', 'free')->first();
        $premiumSub = Subscription::where('slug', 'premium')->first();

        $opportunities = [
            ['title' => 'Tech Grant 2025', 'funding_type' => 'grant', 'deadline' => now()->addMonths(3), 'funding_min' => 10000, 'funding_max' => 50000],
            ['title' => 'Seed Equity Fund', 'funding_type' => 'equity', 'deadline' => now()->addMonths(2), 'funding_min' => 50000, 'funding_max' => 200000],
            ['title' => 'Innovation Prize', 'funding_type' => 'prize', 'deadline' => now()->addMonth(), 'funding_min' => 5000, 'funding_max' => 25000],
            ['title' => 'Growth Debt', 'funding_type' => 'debt', 'deadline' => now()->addMonths(4), 'funding_min' => 100000, 'funding_max' => 500000],
        ];

        foreach ($opportunities as $opp) {
            Opportunity::withoutGlobalScopes()->firstOrCreate(
                ['title' => $opp['title']],
                array_merge($opp, ['id' => (string) Str::uuid(), 'status' => 'active'])
            );
        }
    }
}
