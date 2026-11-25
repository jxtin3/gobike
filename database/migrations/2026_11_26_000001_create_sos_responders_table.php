<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Several GoBikers can now be sent to one SOS: sending another one adds them instead of taking the
 * first one off it. One row per GoBiker sent; a GoBiker who declines keeps their row (with the
 * reason) and can be sent again.
 *
 * sos_requests.assigned_gobiker_id stays, as the newest GoBiker still on the way, for app versions
 * and pages that only know one. Every SOS assigned before this gets its row here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sos_responders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sos_request_id')->constrained('sos_requests')->cascadeOnDelete();
            $table->foreignId('gobiker_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('responding'); // responding | declined
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('decline_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['sos_request_id', 'gobiker_id']);
        });

        DB::table('sos_requests')
            ->whereNotNull('assigned_gobiker_id')
            ->orderBy('id')
            ->each(function (object $sos) {
                $declined = $sos->status === 'declined' && (int) $sos->declined_by_gobiker_id === (int) $sos->assigned_gobiker_id;

                DB::table('sos_responders')->insert([
                    'sos_request_id' => $sos->id,
                    'gobiker_id' => $sos->assigned_gobiker_id,
                    'status' => $declined ? 'declined' : 'responding',
                    'assigned_by' => $sos->handled_by,
                    'assigned_at' => $sos->responded_at ?? $sos->created_at,
                    'declined_at' => $declined ? $sos->declined_at : null,
                    'decline_reason' => $declined ? $sos->decline_reason : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_responders');
    }
};
