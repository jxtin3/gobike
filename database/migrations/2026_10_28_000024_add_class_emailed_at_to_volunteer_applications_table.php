<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** When the volunteer was emailed their class date and venue (null: not emailed yet). */
    public function up(): void
    {
        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->timestamp('class_emailed_at')->nullable()->after('volunteer_class_id');
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->dropColumn('class_emailed_at');
        });
    }
};
