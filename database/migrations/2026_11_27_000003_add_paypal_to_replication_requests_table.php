<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The chapter training fee can be paid through PayPal while PayMongo is switched off
 * (config/payments.php). These hold the PayPal order and its capture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('replication_requests', function (Blueprint $table) {
            $table->string('paypal_order_id')->nullable()->after('paymongo_payment_id');
            $table->string('paypal_capture_id')->nullable()->after('paypal_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('replication_requests', function (Blueprint $table) {
            $table->dropColumn(['paypal_order_id', 'paypal_capture_id']);
        });
    }
};
