<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Donors can give in their own currency. `amount` and `platform_fee` are in that currency;
     * `amount_usd` and `platform_fee_usd` stay as USD equivalents for admin totals.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->char('currency', 3)->default('USD')->after('frequency');
            $table->decimal('amount', 12, 2)->nullable()->after('currency');
            $table->decimal('platform_fee', 10, 2)->default(0)->after('amount');
        });

        DB::table('donations')->update([
            'amount' => DB::raw('amount_usd'),
            'platform_fee' => DB::raw('platform_fee_usd'),
        ]);
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['currency', 'amount', 'platform_fee']);
        });
    }
};
