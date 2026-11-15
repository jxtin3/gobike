<?php

namespace App\Console\Commands;

use App\Models\PaypalDonationPlan;
use App\Services\PayPalDonationService;
use App\Support\Currency;
use Illuminate\Console\Command;
use Throwable;

class SetupPayPalDonationPlan extends Command
{
    protected $signature = 'paypal:setup-donation-plan
        {--fresh : Forget the saved plans for the current mode and create new ones (after switching PayPal account or app)}';

    protected $description = 'Create (or show) the PayPal billing plans for monthly donations, one per donation currency. Optional: plans are also created automatically on first use.';

    public function handle(PayPalDonationService $paypal): int
    {
        $mode = (string) config('paypal.mode', 'sandbox');
        $this->info("Using PayPal [{$mode}] mode");

        if ($this->option('fresh')) {
            $removed = PaypalDonationPlan::where('mode', $mode)->delete();
            $this->warn("Removed {$removed} saved {$mode} plan(s). Existing monthly donors keep their PayPal subscriptions.");
        }

        $failed = false;

        foreach (Currency::codes() as $currency) {
            try {
                $this->line("{$currency}: ".$paypal->planIdFor($currency));
            } catch (Throwable $e) {
                $this->error("{$currency}: ".$e->getMessage());
                $failed = true;
            }
        }

        if ($productId = PaypalDonationPlan::where('mode', $mode)->value('product_id')) {
            $this->line("Product: {$productId}");
        }

        $this->newLine();
        $this->comment('Plans are saved in the paypal_donation_plans table; nothing needs to be added to .env.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
