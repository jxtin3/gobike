<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the GoBiker who recorded it
            $table->string('barangay')->nullable();
            $table->string('name');
            $table->string('address');
            $table->string('contact', 20);
            $table->unsignedTinyInteger('age');
            $table->unsignedSmallInteger('sys');
            $table->unsignedSmallInteger('dia');
            $table->unsignedSmallInteger('pulse');
            $table->unsignedSmallInteger('resp');
            $table->decimal('temp', 4, 1);
            $table->decimal('height', 5, 1);
            $table->decimal('weight', 5, 1);
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'recorded_at']);
            $table->index('barangay');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};