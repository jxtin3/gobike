<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Residents' 1-5 star ratings of a GoBiker after an SOS response or a check-up. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('gobiker_id')->nullable()->constrained('users')->nullOnDelete();
            // sos: an SOS request id; checkup: a patients (check-up) id
            $table->string('kind', 20);
            $table->unsignedBigInteger('subject_id');
            // null: the resident skipped the rating
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('comment', 500)->nullable();
            $table->timestamps();

            $table->unique(['kind', 'subject_id']);
            $table->index(['gobiker_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
