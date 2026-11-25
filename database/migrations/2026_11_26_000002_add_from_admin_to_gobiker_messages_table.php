<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin can start a conversation with a GoBiker, not only answer one. Such a row's
 * `message` is the admin's text (user_id is still the GoBiker it is for).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gobiker_messages', function (Blueprint $table) {
            $table->boolean('from_admin')->default(false)->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('gobiker_messages', function (Blueprint $table) {
            $table->dropColumn('from_admin');
        });
    }
};
