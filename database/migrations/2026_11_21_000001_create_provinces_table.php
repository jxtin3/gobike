<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('region', 100)->nullable();
            $table->string('code', 10)->nullable(); // PSGC code
            $table->timestamps();

            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};