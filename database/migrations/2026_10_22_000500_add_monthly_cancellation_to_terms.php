<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Mentions the new "Manage or cancel your monthly donation" page in the published Terms.
 * Only section 9 is replaced (matched by title, from resources/legal/terms.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('legal_documents')->where('key', 'terms')->first();

        if (! $row) {
            return; // the page falls back to the file text, which is already up to date
        }

        $source = collect((require resource_path('legal/terms.php'))['sections'])->keyBy('title');
        $sections = json_decode($row->sections ?? '[]', true) ?: [];

        foreach ($sections as $i => $section) {
            if (($section['title'] ?? '') === '9. Donations' && $source->has('9. Donations')) {
                $sections[$i]['content'] = $source['9. Donations']['content'];
            }
        }

        DB::table('legal_documents')->where('key', 'terms')->update([
            'sections' => json_encode($sections),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Text-only change; nothing to undo.
    }
};
