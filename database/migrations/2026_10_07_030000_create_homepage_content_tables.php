<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('abbreviation', 40);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('impact_stats', function (Blueprint $table) {
            $table->id();
            $table->string('section')->default('impact');
            $table->string('label', 100);
            $table->string('value', 40);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();


        DB::table('impact_stats')->insert([
            ['section' => 'impact', 'label' => 'Trained Youth Volunteers', 'value' => '2,500+', 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'impact', 'label' => 'Barangays Served', 'value' => '45', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'impact', 'label' => 'Volunteer Hours', 'value' => '18,000+', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'impact', 'label' => 'Active Since', 'value' => '2019', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'highlight', 'label' => 'Medical Missions Conducted', 'value' => '300+', 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'highlight', 'label' => 'Beneficiaries Served', 'value' => '12,000', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'highlight', 'label' => 'Barangays Reached', 'value' => '45', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['section' => 'highlight', 'label' => 'Youth-Led Initiatives', 'value' => '85%', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('impact_stats');
        Schema::dropIfExists('home_partners');
    }
};
