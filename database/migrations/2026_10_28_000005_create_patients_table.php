<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per check-up a GoBiker records. Blood sugar (mg/dL) is optional. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the GoBiker who recorded it
            // The resident's account, when the person checked has one (matched by name and mobile).
            $table->foreignId('resident_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('barangay', 100)->nullable();
            $table->string('name');
            $table->string('address');
            $table->string('contact', 20);
            $table->unsignedTinyInteger('age');
            $table->unsignedSmallInteger('sys');
            $table->unsignedSmallInteger('dia');
            $table->unsignedSmallInteger('pulse');
            $table->unsignedSmallInteger('resp');
            $table->decimal('temp', 4, 1);
            $table->unsignedSmallInteger('blood_sugar')->nullable();
            // The GoBiker's advice after the check-up. The resident sees it in their visit history.
            $table->text('remarks')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'recorded_at']);
            $table->foreign('barangay')->references('name')->on('barangays')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
