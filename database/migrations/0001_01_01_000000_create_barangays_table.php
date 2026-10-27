<?php

use App\Support\Barangays;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The barangays of Bugallon (App\Support\Barangays::ALL). Every table that stores a barangay for
 * a user, check-up, ronda, SOS or visit points here by name, so an unknown barangay is refused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('municipality', 100)->default('Bugallon');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('barangays')->insert(collect(Barangays::ALL)->map(fn (string $name, int $i) => [
            'name' => $name,
            'sort_order' => $i,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};
