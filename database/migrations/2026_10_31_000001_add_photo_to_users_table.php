<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The profile photo a GoBiker uploads when they sign up, so the resident they are sent to and the
 * admin can see who is responding.
 *
 * Nullable: accounts made before this was required have none, and they keep working with an
 * initials badge in place of a photo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('barangay');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
