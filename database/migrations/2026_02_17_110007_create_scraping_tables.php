<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('base_url');
            $table->string('scraping_strategy')->default('default');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('scraping_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained('opportunity_sources')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('items_found')->default(0);
            $table->timestamps();
        });

        Schema::create('scraped_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scraping_run_id')->constrained()->cascadeOnDelete();
            $table->string('external_url');
            $table->longText('raw_content')->nullable();
            $table->string('content_hash')->unique();
            $table->boolean('processed')->default(false);
            $table->boolean('duplicate_detected')->default(false);
            $table->timestamps();

            $table->index(['scraping_run_id', 'processed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraped_entries');
        Schema::dropIfExists('scraping_runs');
        Schema::dropIfExists('opportunity_sources');
    }
};
