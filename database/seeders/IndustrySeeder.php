<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            'Technology',
            'Fintech',
            'HealthTech',
            'Agritech',
            'EdTech',
            'CleanTech',
            'PropTech',
            'RetailTech',
            'Logistics',
            'SaaS',
            'E-commerce',
            'Media',
            'Travel',
            'FoodTech',
            'InsurTech',
            'LegalTech',
            'HRTech',
            'MarTech',
            'Cybersecurity',
            'AI / Machine Learning',
            'Blockchain',
            'Gaming',
            'Sport',
            'Wellness',
            'Manufacturing',
            'Construction',
            'Energy',
            'Automotive',
            'Aerospace',
            'Biotech',
            'Pharma',
            'Nonprofit',
            'Other',
        ];

        foreach ($industries as $name) {
            Industry::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
