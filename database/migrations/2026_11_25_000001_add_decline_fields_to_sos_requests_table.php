<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A GoBiker who was sent to an SOS but cannot go declines it from the app, and
 * a reason is required when they do. The admin sees the reason and either
 * sends another GoBiker or closes the request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->foreignId('declined_by_gobiker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('declined_at')->nullable();
            $table->string('decline_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropForeign(['declined_by_gobiker_id']);
            $table->dropColumn(['declined_by_gobiker_id', 'declined_at', 'decline_reason']);
        });
    }
};
