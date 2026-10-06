<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('impact_stats')
            ->where('section', 'badge')
            ->exists();

        if (! $exists) {
            DB::table('impact_stats')->insert([
                'section' => 'badge',
                'label' => 'Families Reached',
                'value' => '500+',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('impact_stats')
            ->where('section', 'badge')
            ->where('label', 'Families Reached')
            ->where('value', '500+')
            ->delete();
    }
};
