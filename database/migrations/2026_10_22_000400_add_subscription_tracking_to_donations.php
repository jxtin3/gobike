<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Monthly (PayPal subscription) donations: track the subscription's state and record every
 * monthly charge, so cancellations, failed renewals and real totals are visible in admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('subscription_status', 20)->nullable()->after('paypal_subscription_id'); // active, suspended, cancelled, expired
            $table->timestamp('cancelled_at')->nullable()->after('subscription_status');
        });

        Schema::create('donation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20);
            $table->string('gateway_payment_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Monthly donations that were already confirmed are running subscriptions.
        DB::table('donations')
            ->where('frequency', 'monthly')
            ->where('status', 'paid')
            ->whereNotNull('paypal_subscription_id')
            ->update(['subscription_status' => 'active']);
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_payments');

        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'cancelled_at']);
        });
    }
};
