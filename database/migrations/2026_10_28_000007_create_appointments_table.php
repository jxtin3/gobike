<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Home check-up visits residents request in the app; a GoBiker (or the admin) takes them. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('users')->cascadeOnDelete();
            $table->string('barangay', 100);
            $table->string('address', 255);
            $table->date('preferred_date');
            $table->string('preferred_window', 20); // morning | afternoon
            $table->text('reason');
            // pending -> accepted -> completed; or declined (admin) / cancelled (resident)
            $table->string('status', 20)->default('pending');
            $table->foreignId('gobiker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->text('decline_note')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // "Clear history" in the resident app hides a closed visit from the resident only.
            $table->timestamp('resident_hidden_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'barangay']);
            $table->index(['gobiker_id', 'status']);
            $table->foreign('barangay')->references('name')->on('barangays')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
