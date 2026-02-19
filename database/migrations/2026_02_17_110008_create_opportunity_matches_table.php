<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_matches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('startup_id');
            $table->uuid('opportunity_id');
            $table->unsignedInteger('score')->default(0);
            $table->json('score_breakdown')->nullable();
            $table->timestamps();

            $table->foreign('startup_id')->references('id')->on('startups')->cascadeOnDelete();
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();
            $table->unique(['startup_id', 'opportunity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_matches');
    }
};
