<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GoBikers no longer record height and weight; they record blood sugar (mg/dL, optional) instead.
 * The old height and weight numbers stay in the database (hidden everywhere) so nothing is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->decimal('height', 5, 1)->nullable()->change();
            $table->decimal('weight', 5, 1)->nullable()->change();
            $table->unsignedSmallInteger('blood_sugar')->nullable()->after('temp');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('blood_sugar');
        });
    }
};
