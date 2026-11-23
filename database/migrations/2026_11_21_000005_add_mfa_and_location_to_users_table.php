<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // MFA fields
            $table->text('mfa_secret')->nullable(); // encrypted TOTP secret
            $table->timestamp('mfa_enabled_at')->nullable();
            $table->json('mfa_backup_codes')->nullable(); // hashed backup codes

            // Location hierarchy (replacing direct barangay string FK)
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();

            // Keep existing barangay string column for backward compatibility
            // We'll eventually migrate to barangay_id FK
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('municipality_id');
            $table->index('city_id');
            $table->index('province_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['municipality_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['province_id']);
            $table->dropColumn([
                'mfa_secret',
                'mfa_enabled_at',
                'mfa_backup_codes',
                'municipality_id',
                'city_id',
                'province_id',
            ]);
        });
    }
};