<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PayPal monthly donation plans, one per currency and PayPal mode (sandbox/live),
     * created automatically the first time someone donates monthly in that currency.
     */
    public function up(): void
    {
        Schema::create('paypal_donation_plans', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 10);
            $table->char('currency', 3);
            $table->string('product_id');
            $table->string('plan_id');
            $table->timestamps();

            $table->unique(['mode', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypal_donation_plans');
    }
};
