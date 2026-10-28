<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\User;
use App\Support\DemoData;
use Illuminate\Console\Command;

/**
 * Keeps the demo GoBikers who are "on ronda" live on the admin map during a presentation:
 * every few seconds they are marked as just seen. The map shows a GoBiker as offline once
 * their phone has not reported for LOCATION_STALE_AFTER_SECONDS. They stay on their street
 * (a random walk would slowly carry them into rice fields or the river).
 */
class DemoLive extends Command
{
    protected $signature = 'demo:live
        {--minutes=120 : How long to keep them live}
        {--every=10 : Seconds between updates}
        {--once : Update once and stop}';

    protected $description = 'Keep the demo GoBikers on ronda shown as active on the live map (for presentations).';

    public function handle(): int
    {
        $ids = User::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->where('role', 'GoBiker')->pluck('id');
        if ($ids->isEmpty()) {
            $this->error('No demo GoBikers. Run demo:seed first.');

            return self::FAILURE;
        }

        $until = now()->addMinutes((int) $this->option('minutes'));
        $every = max(3, (int) $this->option('every'));

        do {
            $updated = Location::whereIn('user_id', $ids)
                ->whereNotNull('active_start_time')
                ->whereNull('active_end_time') // on ronda now
                ->update(['last_seen_at' => now()]);
            $this->line(now()->format('H:i:s')." {$updated} demo GoBikers marked as seen");

            if ($this->option('once')) {
                break;
            }
            sleep($every);
        } while (now()->lt($until));

        return self::SUCCESS;
    }
}
