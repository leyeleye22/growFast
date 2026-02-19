<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\Stage;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StartupDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class]);

        $startupRole = Role::findByName('startup', 'api');

        $this->call([IndustrySeeder::class, StageSeeder::class]);

        $users = [
            ['name' => 'Marie Dupont', 'email' => 'marie@startup.io', 'startup' => ['name' => 'TechFlow', 'industry' => 'technology', 'stage' => 'seed', 'country' => 'FR']],
            ['name' => 'Jean Martin', 'email' => 'jean@innovate.com', 'startup' => ['name' => 'InnovateHealth', 'industry' => 'healthtech', 'stage' => 'series-a', 'country' => 'FR']],
            ['name' => 'Aisha Johnson', 'email' => 'aisha@agritech.com', 'startup' => ['name' => 'AgriSmart', 'industry' => 'agritech', 'stage' => 'seed', 'country' => 'US']],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => bcrypt('password')]
            );
            $user->assignRole($startupRole);

            if (!Startup::where('user_id', $user->id)->where('name', $data['startup']['name'])->exists()) {
                Startup::create(array_merge($data['startup'], ['id' => (string) Str::uuid(), 'user_id' => $user->id]));
            }
        }
    }
}
