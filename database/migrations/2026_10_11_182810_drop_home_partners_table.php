<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('home_partners');
    }

    public function down(): void
    {
        Schema::create('home_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('abbreviation', 40);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
};