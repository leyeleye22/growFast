<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('stages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('opportunity_industry', function (Blueprint $table): void {
            $table->uuid('opportunity_id');
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->primary(['opportunity_id', 'industry_id']);
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();
        });

        Schema::create('opportunity_stage', function (Blueprint $table): void {
            $table->uuid('opportunity_id');
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->primary(['opportunity_id', 'stage_id']);
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();
        });

        Schema::create('opportunity_country', function (Blueprint $table): void {
            $table->uuid('opportunity_id');
            $table->string('country_code', 3);
            $table->primary(['opportunity_id', 'country_code']);
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();
        });

        Schema::create('opportunity_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('opportunity_id');
            $table->string('type')->default('document');
            $table->string('path');
            $table->string('name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_assets');
        Schema::dropIfExists('opportunity_country');
        Schema::dropIfExists('opportunity_stage');
        Schema::dropIfExists('opportunity_industry');
        Schema::dropIfExists('stages');
        Schema::dropIfExists('industries');
    }
};
