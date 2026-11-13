<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How good each position on the live map is: the phone's own error estimate, and when the phone
 * actually measured it (which is not when the server heard about it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->float('accuracy')->nullable()->after('longitude'); // metres; smaller is better
            $table->float('heading')->nullable()->after('accuracy');   // degrees from north
            $table->float('speed')->nullable()->after('heading');      // metres per second
            // When the phone measured the position on the map. last_seen_at only says the app is alive.
            $table->timestamp('position_at')->nullable()->after('speed');
        });

        Schema::table('sos_requests', function (Blueprint $table) {
            $table->float('location_accuracy')->nullable()->after('longitude');
            $table->timestamp('location_recorded_at')->nullable()->after('location_accuracy');
        });
    }

    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropColumn(['location_accuracy', 'location_recorded_at']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['accuracy', 'heading', 'speed', 'position_at']);
        });
    }
};
