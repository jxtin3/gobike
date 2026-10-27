<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GoBikers go on ronda whenever they want, so the admin no longer sets a schedule.
 * Only databases created before this change have the columns (new ones never get them).
 */
return new class extends Migration
{
    private const COLUMNS = ['schedule_days', 'schedule_start', 'schedule_end'];

    public function up(): void
    {
        $existing = array_values(array_filter(self::COLUMNS, fn (string $column) => Schema::hasColumn('users', $column)));

        if ($existing !== []) {
            Schema::table('users', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }

    public function down(): void
    {
        // Nothing to restore: the schedule feature is gone.
    }
};
