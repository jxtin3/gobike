<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Volunteers are trained together in a class of 20–25. A class has a date and a venue;
     * the applications in it point to it. Statuses now follow that flow:
     * pending (new) → waiting (for a full class) → scheduled (in a class) → trained, or rejected.
     */
    public function up(): void
    {
        Schema::create('volunteer_classes', function (Blueprint $table) {
            $table->id();
            $table->date('starts_on');
            $table->string('venue', 150);
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('starts_on');
        });

        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->foreignId('volunteer_class_id')->nullable()->after('application_status')
                ->constrained('volunteer_classes')->nullOnDelete();
        });

        // Approved and waitlisted volunteers were both waiting to be trained.
        DB::table('volunteer_applications')
            ->whereIn('application_status', ['approved', 'waitlisted'])
            ->update(['application_status' => 'waiting']);
    }

    public function down(): void
    {
        DB::table('volunteer_applications')
            ->whereIn('application_status', ['waiting', 'scheduled', 'trained'])
            ->update(['application_status' => 'approved']);

        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('volunteer_class_id');
        });

        Schema::dropIfExists('volunteer_classes');
    }
};
