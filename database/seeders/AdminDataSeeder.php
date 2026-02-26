<?php



namespace Database\Seeders;

use App\Models\Industry;
use App\Models\Stage;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class, SubscriptionSeeder::class]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@growfast.com'],
            [
                'name' => 'Admin GrowFast',
                'password' => bcrypt('password'),
            ]
        );
        if (! $admin->hasRole('admin')) {
            $admin->assignRole(Role::findByName('admin', 'web'));
        }

        $industries = ['Technology', 'Fintech', 'HealthTech', 'Agritech', 'EdTech'];
        foreach ($industries as $name) {
            Industry::firstOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name]
            );
        }

        $stages = ['Idea', 'Seed', 'Series A', 'Growth', 'Mature'];
        foreach ($stages as $name) {
            Stage::firstOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name]
            );
        }

    }
}
