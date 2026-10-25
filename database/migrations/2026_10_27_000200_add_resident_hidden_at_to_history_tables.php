<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Clear history" in the resident app hides finished SOS requests and closed home visits from
 * the resident's own history. The rows stay for the admin (emergency and visit records).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sos_requests', 'appointments'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->timestamp('resident_hidden_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['sos_requests', 'appointments'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('resident_hidden_at');
            });
        }
    }
};
