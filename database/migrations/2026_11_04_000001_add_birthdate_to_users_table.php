<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date of birth, asked at sign-up so the app can refuse anyone under the minimum age and the
 * admin can see how old a GoBiker is before approving them. A birthdate, not an age: an age is
 * out of date the next year.
 *
 * Nullable: accounts made before this have none, and apps that do not send it yet still register.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('barangay');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('birthdate');
        });
    }
};
