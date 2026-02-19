<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            'Idea',
            'Pre-seed',
            'Seed',
            'Series A',
            'Series B',
            'Series C',
            'Growth',
            'Scale-up',
            'Mature',
            'Exit',
        ];

        foreach ($stages as $name) {
            Stage::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
