<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Adds the chapter replication inquiry and early training payment wording to the published
 * Terms & Conditions and Privacy Policy. Only the listed sections are replaced (matched by title,
 * from resources/legal/*.php); anything else an admin has edited is left as it is.
 */
return new class extends Migration
{
    private const SECTIONS = [
        'terms' => ['10. Training Programs and Chapter Requests'],
        'privacy' => ['2. Information We Collect', '4. How We Use Your Information', '8. How Long We Keep Information'],
    ];

    public function up(): void
    {
        foreach (self::SECTIONS as $key => $titles) {
            $row = DB::table('legal_documents')->where('key', $key)->first();

            if (! $row) {
                continue; // the page falls back to the file text, which is already up to date
            }

            $source = require resource_path("legal/{$key}.php");
            $latest = collect($source['sections'])->keyBy('title');
            $sections = json_decode($row->sections ?? '[]', true) ?: [];

            foreach ($sections as $i => $section) {
                $title = $section['title'] ?? '';

                if (in_array($title, $titles, true) && $latest->has($title)) {
                    $sections[$i]['content'] = $latest[$title]['content'];
                }
            }

            DB::table('legal_documents')->where('key', $key)->update([
                'sections' => json_encode($sections),
                'last_updated' => $source['last_updated'],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Text-only change; nothing to undo.
    }
};
