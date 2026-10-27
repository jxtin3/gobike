<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            // Typed by the resident (house no., street, purok). Older requests have none.
            $table->string('address', 255)->nullable()->after('barangay');
        });
    }

    public function down(): void
    {
        Schema::table('sos_requests', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
