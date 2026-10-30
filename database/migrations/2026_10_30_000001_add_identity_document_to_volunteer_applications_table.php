<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The ID(s) or barangay certificate a volunteer uploads to prove who they are and where they live.
     * Null on applications made before this was required.
     */
    public function up(): void
    {
        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->string('id_type', 30)->nullable()->after('province');
            $table->json('id_paths')->nullable()->after('id_type');
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->dropColumn(['id_type', 'id_paths']);
        });
    }
};
