<?php

namespace App\Console\Commands;

use App\Support\DemoData;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class DemoClear extends Command
{
    use ConfirmableTrait;

    protected $signature = 'demo:clear {--force : Skip the confirmation on production}';

    protected $description = 'Remove the fake presentation data added by demo:seed. Real records are not touched.';

    public function handle(): int
    {
        if (! $this->confirmToProceed('This removes all demo data (accounts ending in @'.DemoData::EMAIL_DOMAIN.').')) {
            return self::FAILURE;
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
