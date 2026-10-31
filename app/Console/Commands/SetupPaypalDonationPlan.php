<?php

namespace App\Console\Commands;

use App\Services\PayPalDonationService;
use App\Support\Currency;
use Illuminate\Console\Command;
use Throwable;

class SetupPayPalDonationPlan extends Command
{
    protected $signature = 'paypal:setup-donation-plan';

    protected $description = 'Create (or show) the PayPal billing plans for monthly donations, one per donation currency. Optional: plans are also created automatically on first use.';

    public function handle(PayPalDonationService $paypal): int
    {
        $mode = config('paypal.mode', 'sandbox');
        $this->info("Using PayPal [{$mode}] mode");

        $failed = false;

        foreach (Currency::codes() as $currency) {
            try {
                $this->line("{$currency}: ".$paypal->planIdFor($currency));
            } catch (Throwable $e) {
                $this->error("{$currency}: ".$e->getMessage());
                $failed = true;
            }
        }

        $this->newLine();
        $this->comment('Plans are saved in the paypal_donation_plans table; nothing needs to be added to .env.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
