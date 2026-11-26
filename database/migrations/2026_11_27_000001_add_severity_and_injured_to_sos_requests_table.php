<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The resident now says how serious the emergency is and whether anyone is hurt. Both are
 * optional here: app versions from before this change send neither, and an SOS must never be
 * turned away for it. See SosRequest::SEVERITIES and SosRequest::INJURED.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->string('severity', 10)->nullable()->after('description');
            $table->string('injured', 10)->nullable()->after('severity');
        });
    }

    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropColumn(['severity', 'injured']);
        });
    }
};
