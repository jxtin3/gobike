<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** True when the resident marked their own SOS as resolved (no longer needs help). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->boolean('resolved_by_resident')->default(false)->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropColumn('resolved_by_resident');
        });
    }
};
