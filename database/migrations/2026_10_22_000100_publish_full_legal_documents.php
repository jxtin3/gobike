<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Replaces the short placeholder Privacy Policy and Terms with the full versions and adds the
 * Cookie Policy. The text lives in resources/legal/*.php; after this runs, admins edit it in Admin → Legal.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['privacy', 'terms', 'cookies'] as $key) {
            $document = require resource_path("legal/{$key}.php");

            DB::table('legal_documents')->updateOrInsert(
                ['key' => $key],
                [
                    'title' => $document['title'],
                    'last_updated' => $document['last_updated'],
                    'sections' => json_encode($document['sections']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        // The earlier placeholder text is not restored; only the Cookie Policy (new here) is removed.
        DB::table('legal_documents')->where('key', 'cookies')->delete();
    }
};
