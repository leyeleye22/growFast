<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('grant_name');
            $table->decimal('award_amount_min', 15, 2)->nullable();
            $table->decimal('award_amount_max', 15, 2)->nullable();
            $table->string('application_link')->nullable();
            $table->date('deadline')->nullable();
            $table->string('location_eligibility')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_suggestions');
    }
};
