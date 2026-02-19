<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table): void {
            $table->string('tagline')->nullable()->after('name');
            $table->date('founding_date')->nullable()->after('description');
            $table->string('pitch_video_url')->nullable()->after('founding_date');
            $table->string('phone')->nullable()->after('website');
            $table->string('social_media')->nullable()->after('phone');
            $table->string('customer_type')->nullable()->after('industry'); // B2B, B2C, B2B2C, B2G, etc.
            $table->decimal('revenue_min', 15, 2)->nullable()->after('country');
            $table->decimal('revenue_max', 15, 2)->nullable()->after('revenue_min');
            $table->string('ownership_type')->nullable()->after('revenue_max'); // minority, women, veteran
        });
    }

    public function down(): void
    {
        Schema::table('startups', function (Blueprint $table): void {
            $table->dropColumn([
                'tagline',
                'founding_date',
                'pitch_video_url',
                'phone',
                'social_media',
                'customer_type',
                'revenue_min',
                'revenue_max',
                'ownership_type',
            ]);
        });
    }
};
