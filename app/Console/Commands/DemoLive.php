<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\User;
use App\Support\DemoData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the demo GoBikers who are "on ronda" live on the admin map during a presentation:
 * every few seconds they are marked as just seen and move a few metres. The map shows a
 * GoBiker as offline once their phone has not reported for LOCATION_STALE_AFTER_SECONDS.
 */
class DemoLive extends Command
{
    protected $signature = 'demo:live
        {--minutes=120 : How long to keep them moving}
        {--every=10 : Seconds between updates}
        {--once : Update once and stop}';

    protected $description = 'Keep the demo GoBikers on ronda moving on the live map (for presentations).';

    public function handle(): int
    {
        $ids = User::where('email', 'like', '%@'.DemoData::EMAIL_DOMAIN)->where('role', 'GoBiker')->pluck('id');
        if ($ids->isEmpty()) {
            $this->error('No demo GoBikers. Run demo:seed first.');

            return self::FAILURE;
        }

        // About 30 metres per step at most, in any direction.
        $step = DB::getDriverName() === 'sqlite'
            ? '((abs(random()) % 1000) / 1000.0 - 0.5) * 0.0006'
            : '(RAND() - 0.5) * 0.0006';

        $until = now()->addMinutes((int) $this->option('minutes'));
        $every = max(3, (int) $this->option('every'));

        do {
            $moved = Location::whereIn('user_id', $ids)
                ->whereNotNull('active_start_time')
                ->whereNull('active_end_time') // on ronda now
                ->update([
                    'last_seen_at' => now(),
                    'latitude' => DB::raw("latitude + {$step}"),
                    'longitude' => DB::raw("longitude + {$step}"),
                ]);
            $this->line(now()->format('H:i:s')." {$moved} demo GoBikers updated");

            if ($this->option('once')) {
                break;
            }
            sleep($every);
        } while (now()->lt($until));

        return self::SUCCESS;
    }
}
