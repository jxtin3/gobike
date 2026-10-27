<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Volunteer sign-ups from the website. The address is free text: applicants can live anywhere. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_applications', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 150);
            $table->string('phone', 30);
            $table->unsignedTinyInteger('age');
            $table->string('occupation_status', 30);
            $table->string('school', 150)->nullable();
            $table->string('barangay', 100);
            $table->string('municipality', 100);
            $table->string('province', 100);
            $table->string('availability', 20);
            $table->boolean('has_bike')->default(false);
            $table->text('motivation')->nullable();
            $table->string('emergency_name', 100);
            $table->string('emergency_phone', 30);
            $table->string('guardian_name', 100)->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->boolean('guardian_consent')->default(false);
            $table->string('application_status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_applications');
    }
};
