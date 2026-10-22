<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // When the GoBiker sent the emergency that is still open (cleared once an admin resolves it).
            $table->timestamp('emergency_at')->nullable()->after('status');
        });

        // Emergencies already open before this column existed: best guess is their last update.
        DB::table('locations')
            ->whereRaw('LOWER(status) IN (?, ?)', ['emergency', 'responding'])
            ->update(['emergency_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('emergency_at');
        });
    }
};
