<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Publishes version 2.0 of the Privacy Policy and Terms & Conditions, and the updated Cookie
 * Policy, from resources/legal/*.php. They describe the RondaGo app, the move to Hostinger and
 * the paused online payments. This replaces the copies in the database; the earlier text stays
 * in git history.
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
        // The earlier text is in git history; nothing to restore here.
    }
};
