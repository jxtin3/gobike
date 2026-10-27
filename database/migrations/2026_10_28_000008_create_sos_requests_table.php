<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Emergency requests residents send from the mobile app (the SOS button). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sos_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('municipality', 100);
            $table->string('barangay', 100);
            $table->string('landmark', 255);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            // sent -> responding -> resolved
            $table->string('status', 20)->default('sent');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            // True when the resident marked their own SOS as resolved (no longer needs help).
            $table->boolean('resolved_by_resident')->default(false);
            // "Clear history" in the resident app hides a finished request from the resident only.
            $table->timestamp('resident_hidden_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->foreign('barangay')->references('name')->on('barangays')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_requests');
    }
};
