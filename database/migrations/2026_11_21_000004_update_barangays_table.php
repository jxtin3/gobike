<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            // Add foreign keys to location hierarchy
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();

            // Keep the old municipality string column for backward compatibility during transition
            // We'll drop it in a future migration once all data is migrated
        });

        // Add indexes for common queries
        Schema::table('barangays', function (Blueprint $table) {
            $table->index('municipality_id');
            $table->index('city_id');
            $table->index('province_id');
        });
    }

    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropForeign(['municipality_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['province_id']);
            $table->dropColumn(['municipality_id', 'city_id', 'province_id']);
        });
    }
};