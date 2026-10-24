<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A GoBiker's ronda schedule, set by an admin and shown on the GoBiker's dashboard. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('schedule_days')->nullable()->after('barangay'); // e.g. ["Mon","Wed","Fri"]
            $table->time('schedule_start')->nullable()->after('schedule_days');
            $table->time('schedule_end')->nullable()->after('schedule_start');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['schedule_days', 'schedule_start', 'schedule_end']);
        });
    }
};
