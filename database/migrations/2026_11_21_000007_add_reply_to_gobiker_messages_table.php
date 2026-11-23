<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gobiker_messages', function (Blueprint $table) {
            $table->foreignId('reply_from_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reply_message')->nullable();
            $table->timestamp('replied_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gobiker_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_from_admin_id']);
            $table->dropColumn(['reply_from_admin_id', 'reply_message', 'replied_at']);
        });
    }
};