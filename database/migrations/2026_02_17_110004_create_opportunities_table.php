<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('funding_type')->nullable();
            $table->date('deadline')->nullable();
            $table->decimal('funding_min', 15, 2)->nullable();
            $table->decimal('funding_max', 15, 2)->nullable();
            $table->string('status')->default('active');
            $table->uuid('subscription_required_id')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->string('external_url')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscription_required_id')->references('id')->on('subscriptions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
