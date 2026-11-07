<?php

namespace App\Jobs;

use App\Mail\VolunteerClassScheduled;
use App\Models\VolunteerClass;
use App\Support\BulkMailer;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Emails a class's volunteers their date and venue. Run after the admin's page has been
 * sent (the server has no queue worker), so scheduling a class of 25 does not keep them
 * waiting. Only volunteers not emailed yet are sent to, so it is safe to run again.
 */
class EmailVolunteerClass
{
    use Dispatchable;

    public function __construct(public int $classId) {}

    public function handle(): void
    {
        $class = VolunteerClass::find($this->classId);
        if (! $class) {
            return;
        }

        set_time_limit(0);

        $applications = $class->applications()
            ->where('application_status', 'scheduled')
            ->whereNull('class_emailed_at')
            ->orderBy('id')
            ->get();

        BulkMailer::send(
            $applications,
            fn ($application) => [$application->email, $application->full_name, new VolunteerClassScheduled($application, $class)],
            fn ($application) => $application->forceFill(['class_emailed_at' => now()])->save(),
            'volunteer class email',
        );
    }
}
