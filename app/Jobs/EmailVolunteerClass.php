<?php

namespace App\Jobs;

use App\Mail\VolunteerClassScheduled;
use App\Models\VolunteerClass;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

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

        $class->applications()
            ->where('application_status', 'scheduled')
            ->whereNull('class_emailed_at')
            ->orderBy('id')
            ->each(function ($application) use ($class) {
                try {
                    Mail::to($application->email, $application->full_name)
                        ->send(new VolunteerClassScheduled($application, $class));
                    $application->forceFill(['class_emailed_at' => now()])->save();
                } catch (TransportException $e) {
                    // The mail server itself can't be reached: the others would fail the same way.
                    Log::error('Volunteer class emails stopped: the mail server could not be reached', [
                        'class_id' => $class->id,
                        'error' => $e->getMessage(),
                    ]);

                    return false;
                } catch (Throwable $e) {
                    // One bad address must not stop the rest; the admin can send again or call.
                    Log::warning('Could not email volunteer class details', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }
}
