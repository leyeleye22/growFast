<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->uuid('startup_id');
            $table->uuid('opportunity_id');
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamps();

            $table->foreign('startup_id')->references('id')->on('startups')->cascadeOnDelete();
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();
            $table->unique(['startup_id', 'opportunity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_opportunities');
    }
};
