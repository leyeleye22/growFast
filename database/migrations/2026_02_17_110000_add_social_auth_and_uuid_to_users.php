<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('google_id')->nullable()->unique()->after('remember_token');
            $table->string('linkedin_id')->nullable()->unique()->after('google_id');
            $table->string('avatar')->nullable()->after('linkedin_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['uuid', 'google_id', 'linkedin_id', 'avatar']);
        });
    }
};
