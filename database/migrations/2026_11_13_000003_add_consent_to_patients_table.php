<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Whether the person (or their parent or guardian) agreed to the check-up. Null means it was not
 * recorded: check-ups from before this column, or from app versions that did not ask yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('consent_given')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('consent_given');
        });
    }
};
