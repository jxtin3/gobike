<?php

namespace App\Console\Commands;

use App\Support\DemoData;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class DemoClear extends Command
{
    use ConfirmableTrait;

    protected $signature = 'demo:clear
        {--map-only : Only take the demo GoBikers off the live map, keep every demo record}
        {--force : Skip the confirmation on production}';

    protected $description = 'Remove the fake presentation data added by demo:seed. Real records are not touched.';

    public function handle(): int
    {
        $mapOnly = (bool) $this->option('map-only');

        if (! $this->confirmToProceed($mapOnly
            ? 'This removes the demo GoBikers (accounts ending in @'.DemoData::EMAIL_DOMAIN.') from the live map.'
            : 'This removes all demo data (accounts ending in @'.DemoData::EMAIL_DOMAIN.').')) {
            return self::FAILURE;
        }

        if ($mapOnly) {
            $this->info(DemoData::clearMap().' demo GoBikers removed from the live map.');

            return self::SUCCESS;
        }

        $removed = DemoData::clear();
        if ($removed === []) {
            $this->info('No demo data found.');

            return self::SUCCESS;
        }

        $this->table(['Removed', 'Count'], collect($removed)->map(fn ($n, $kind) => [$kind, $n])->values());

        return self::SUCCESS;
    }
}
