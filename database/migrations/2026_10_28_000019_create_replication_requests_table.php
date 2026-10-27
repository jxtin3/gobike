<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inquiries from organizations that want to replicate the Go Bike Project, with optional early online payment.
        Schema::create('replication_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('organization', 150);
            $table->string('organization_type', 30);
            $table->string('contact_name', 100);
            $table->string('position', 100)->nullable();
            $table->string('email', 150);
            $table->string('phone', 30);
            $table->string('municipality', 100);
            $table->string('province', 100);
            $table->unsignedSmallInteger('trainees');
            $table->date('preferred_date')->nullable();
            $table->text('message')->nullable();
            $table->decimal('amount_php', 10, 2);
            $table->string('payment_status', 20)->default('unpaid'); // unpaid, pending, paid, refunded
            $table->string('paymongo_session_id')->nullable();
            $table->string('paymongo_payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending, contacted, scheduled, completed, cancelled
            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replication_requests');
    }
};
