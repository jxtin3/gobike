<?php

namespace App\Jobs;

use App\Mail\GoBikerApproved;
use App\Models\User;
use App\Support\BulkMailer;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Emails approved GoBikers who have not been told yet that they can sign in. Run after the
 * admin's page has been sent (the server has no queue worker). Only those not emailed yet are
 * sent to, so it is safe to run again; each run sends at most BATCH so one click stays well
 * under the mail server's sending limits.
 */
class EmailApprovedGoBikers
{
    use Dispatchable;

    public const BATCH = 100;

    public function handle(): void
    {
        set_time_limit(0);

        BulkMailer::send(
            User::awaitingApprovalEmail()->orderBy('id')->limit(self::BATCH)->get(),
            fn (User $user) => [$user->email, $user->name, new GoBikerApproved($user)],
            fn (User $user) => $user->forceFill(['approval_emailed_at' => now()])->save(),
            'GoBiker approval email',
        );
    }
}
