<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table): void {
            $table->decimal('funding_min', 15, 2)->nullable()->after('ownership_type');
            $table->decimal('funding_max', 15, 2)->nullable()->after('funding_min');
            $table->json('funding_types')->nullable()->after('funding_max'); // grant, equity, debt, prize
            $table->json('preferred_industries')->nullable()->after('funding_types');
            $table->json('preferred_stages')->nullable()->after('preferred_industries');
            $table->json('preferred_countries')->nullable()->after('preferred_stages');
            $table->date('deadline_min')->nullable()->after('preferred_countries');
            $table->date('deadline_max')->nullable()->after('deadline_min');
        });
    }

    public function down(): void
    {
        Schema::table('startups', function (Blueprint $table): void {
            $table->dropColumn([
                'funding_min',
                'funding_max',
                'funding_types',
                'preferred_industries',
                'preferred_stages',
                'preferred_countries',
                'deadline_min',
                'deadline_max',
            ]);
        });
    }
};
