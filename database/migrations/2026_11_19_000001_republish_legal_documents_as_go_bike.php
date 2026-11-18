<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * The mobile app was renamed from RondaGo to Go Bike. resources/legal/*.php already says Go Bike,
 * but the live legal pages are read from the database, which still held the RondaGo text from
 * 2026_11_13_000001. This copies the current files into the database again.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['privacy', 'terms', 'cookies'] as $key) {
            $document = require resource_path("legal/{$key}.php");

            $exists = DB::table('legal_documents')->where('key', $key)->exists();

            DB::table('legal_documents')->updateOrInsert(['key' => $key], [
                'title' => $document['title'],
                'last_updated' => $document['last_updated'],
                'sections' => json_encode($document['sections']),
                'updated_at' => now(),
            ] + ($exists ? [] : ['created_at' => now()]));
        }
    }

    public function down(): void
    {
        // The RondaGo text is in git history; nothing to restore here.
    }
};
