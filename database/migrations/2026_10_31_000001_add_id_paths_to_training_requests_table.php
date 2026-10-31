<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The ID photos a person uploads with a paid training request so the team can verify who they are.
     * Null on requests made before this was required.
     */
    public function up(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->json('id_paths')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn('id_paths');
        });
    }
};
