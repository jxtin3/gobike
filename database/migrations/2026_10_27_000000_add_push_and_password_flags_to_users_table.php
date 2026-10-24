<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - has_password: false for accounts made with Google sign-in, whose password is a random one
 *   nobody knows. They may set a password without giving a "current" one.
 * - notification_prefs: which push notifications a resident wants (from the app's switches).
 * - device_tokens: the phones that receive push notifications (Firebase Cloud Messaging).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_password')->default(true)->after('password');
            $table->json('notification_prefs')->nullable()->after('schedule_end');
        });

        // Accounts created through Google until now: never had a password of their own.
        DB::table('users')->whereNotNull('google_id')->whereNull('mobile')->update(['has_password' => false]);

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_password', 'notification_prefs']);
        });
    }
};
