<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin's decision on whether GoBikers should keep checking a person.
 * Check-ups have no patient ID, so the person is identified by name + contact (person_key).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->string('person_key')->unique();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->string('decision', 20); // continue, stop
            $table->text('note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_follow_ups');
    }
};
