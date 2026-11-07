<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When an admin approved a GoBiker sign-up, and when the "you're approved" email reached them.
 *
 * Both stay null for accounts approved before this: they were never meant to get the email,
 * so the "email approved GoBikers" button only ever reaches approvals from now on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->timestamp('approval_emailed_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'approval_emailed_at']);
        });
    }
};
