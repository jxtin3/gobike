<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Donations, and every charge of a monthly (PayPal subscription) donation, so cancellations,
 * failed renewals and real totals are visible in admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('donor_type')->default('individual');
            $table->string('org_name')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('postcode');
            $table->string('state');
            $table->string('country', 10);
            $table->string('other_country')->nullable();
            $table->decimal('amount_usd', 10, 2);
            $table->decimal('amount_php', 12, 2)->nullable();
            $table->decimal('platform_fee_usd', 8, 2)->default(0);
            $table->string('frequency')->default('once');
            $table->string('payment_method');
            $table->string('status')->default('pending');
            // Looked up by the PayPal and PayMongo webhooks.
            $table->string('paypal_order_id')->nullable()->index();
            $table->string('paypal_subscription_id')->nullable()->index();
            $table->string('subscription_status', 20)->nullable(); // active, suspended, cancelled, expired
            $table->timestamp('cancelled_at')->nullable();
            $table->string('paymongo_session_id')->nullable()->index();
            $table->string('paymongo_payment_id')->nullable();
            $table->string('receipt_email')->nullable();
            $table->timestamps();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_payments');
        Schema::dropIfExists('donations');
    }
};
