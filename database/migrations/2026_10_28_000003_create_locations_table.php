<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Each GoBiker's live position on the admin map: one row per GoBiker. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('designated_barangay', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 7)->nullable();
            $table->string('status')->default('active'); // active, offline, emergency, responding
            // When the GoBiker sent the emergency that is still open (cleared once an admin resolves it).
            $table->timestamp('emergency_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('active_start_time')->nullable();
            $table->timestamp('active_end_time')->nullable();
            $table->timestamps();

            $table->foreign('designated_barangay')->references('name')->on('barangays')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
