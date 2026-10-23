<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetupPayPalDonationPlan extends Command
{
    protected $signature = 'paypal:setup-donation-plan';

    protected $description = 'Create the PayPal Product + Billing Plan used for recurring monthly donations, and print the IDs to save in .env';

    public function handle(): int
    {
        $mode = config('paypal.mode', 'sandbox');
        $clientId = config("paypal.{$mode}.client_id");
        $clientSecret = config("paypal.{$mode}.client_secret");

        if (! $clientId || ! $clientSecret) {
            $prefix = 'PAYPAL_'.strtoupper((string) $mode);
            $this->error("PayPal {$mode} credentials are not set. Check {$prefix}_CLIENT_ID / {$prefix}_CLIENT_SECRET in .env.");

            return self::FAILURE;
        }

        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->info("Using PayPal [{$mode}] mode at {$baseUrl}");

        // 1. Get an access token
        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (! $tokenResponse->successful()) {
            $this->error('Failed to get PayPal access token: '.$tokenResponse->body());

            return self::FAILURE;
        }

        $accessToken = $tokenResponse->json('access_token');

        // 2. Create the Product (catalog product) — represents "donating to Go Bike"
        $productResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->post("{$baseUrl}/v1/catalogs/products", [
                'name' => 'Go Bike Monthly Donation',
                'description' => 'Recurring monthly donation to Go Bike',
                'type' => 'SERVICE',
            ]);

        if (! $productResponse->successful()) {
            $this->error('Failed to create PayPal product: '.$productResponse->body());

            return self::FAILURE;
        }

        $productId = $productResponse->json('id');
        $this->info("Created product: {$productId}");

        // 3. Create the Billing Plan — one plan, amount is overridden per-subscription at checkout
        $planResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->post("{$baseUrl}/v1/billing/plans", [
                'product_id' => $productId,
                'name' => 'Go Bike Monthly Donation Plan',
                'description' => 'Charges the donor-selected amount every month',
                'billing_cycles' => [
                    [
                        'frequency' => [
                            'interval_unit' => 'MONTH',
                            'interval_count' => 1,
                        ],
                        'tenure_type' => 'REGULAR',
                        'sequence' => 1,
                        'total_cycles' => 0, // 0 = infinite, runs until cancelled
                        'pricing_scheme' => [
                            'fixed_price' => [
                                'value' => '1.00', // placeholder; overridden per subscription
                                'currency_code' => 'USD',
                            ],
                        ],
                    ],
                ],
                'payment_preferences' => [
                    'auto_bill_outstanding' => true,
                    'setup_fee_failure_action' => 'CONTINUE',
                    'payment_failure_threshold' => 3,
                ],
            ]);

        if (! $planResponse->successful()) {
            $this->error('Failed to create PayPal billing plan: '.$planResponse->body());

            return self::FAILURE;
        }

        $planId = $planResponse->json('id');
        $this->info("Created plan: {$planId}");

        $this->newLine();
        $this->info('Add these to your .env file:');
        $this->line("PAYPAL_DONATION_PRODUCT_ID={$productId}");
        $this->line("PAYPAL_DONATION_PLAN_ID={$planId}");
        $this->newLine();
        $this->comment('Then run: php artisan config:clear');

        return self::SUCCESS;
    }
}