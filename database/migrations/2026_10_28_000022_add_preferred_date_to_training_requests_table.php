<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Individual training starts on a date the person picks (unlike volunteer classes,
     * which wait until 20–25 people have signed up). Older requests have no date.
     */
    public function up(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->date('preferred_date')->nullable()->after('tier');
            $table->string('preferred_window', 20)->nullable()->after('preferred_date');
            $table->index('preferred_date');
        });
    }

    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropIndex(['preferred_date']);
            $table->dropColumn(['preferred_date', 'preferred_window']);
        });
    }
};
