<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The GoBiker the admin sends to an SOS, and the GoBiker who resolved it (they get the rating). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->foreignId('assigned_gobiker_id')->nullable()->after('handled_by')->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_gobiker_id')->nullable()->after('assigned_gobiker_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by_gobiker_id');
            $table->dropConstrainedForeignId('assigned_gobiker_id');
        });
    }
};
