<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The Privacy Policy, Terms & Conditions and Cookie Policy. The first text comes from
 * resources/legal/*.php; after this runs, admins edit it in Admin → Legal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('title', 150);
            $table->string('last_updated', 50)->nullable();
            $table->json('sections')->nullable();
            $table->timestamps();
        });

        foreach (['privacy', 'terms', 'cookies'] as $key) {
            $document = require resource_path("legal/{$key}.php");

            DB::table('legal_documents')->insert([
                'key' => $key,
                'title' => $document['title'],
                'last_updated' => $document['last_updated'],
                'sections' => json_encode($document['sections']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
