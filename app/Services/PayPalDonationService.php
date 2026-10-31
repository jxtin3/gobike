<?php

namespace App\Services;
use App\Models\PaypalDonationPlan;
use App\Support\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Donation;
use RuntimeException;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalDonationService
{
    private ?PayPalClient $client = null;

    /**
     * The PayPal client is created only when a PayPal call is actually needed,
     * so building this service never contacts PayPal.
     */
    private function client(): PayPalClient
    {
        if ($this->client === null) {
            $this->client = new PayPalClient;
            $this->client->setApiCredentials(config('paypal'));
            $this->client->getAccessToken();
        }

        return $this->client;
    }

    /**
     * @return array{approval_url: string, order_id: string}
     */
    public function createOrder(Donation $donation): array
    {
        $this->ensureConfigured();

        $response = $this->client()->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $donation->uuid,
                    'description' => 'Go Bike Donation',
                    'amount' => [
                        'currency_code' => $donation->currency,
                        'value' => Currency::toDecimalString($donation->total, $donation->currency),
                    ],
                ],
            ],
            'application_context' => [
                'return_url' => route('donate.success', $donation),
                'cancel_url' => route('donate.cancel', $donation),
                'brand_name' => 'Go Bike',
                'user_action' => 'PAY_NOW',
            ],
        ]);

        if (($response['status'] ?? '') !== 'CREATED') {
    Log::error('PayPal order creation failed', ['response' => $response]);

    $reason = $response['message']
        ?? $response['error_description']
        ?? ($response['details'][0]['description'] ?? null)
        ?? ($response['name'] ?? null)
        ?? 'Unexpected response from PayPal.';

    throw new RuntimeException("PayPal order creation failed: {$reason}");
}

        $approvalUrl = collect($response['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if ($approvalUrl === null) {
            throw new RuntimeException('PayPal approval URL not found.');
        }

        return [
            'approval_url' => $approvalUrl,
            'order_id' => $response['id'],
        ];
    }

    /**
     * @return array{approval_url: string, subscription_id: string}
     */
    public function createSubscription(Donation $donation): array
    {
        $this->ensureConfigured();

        $planId = $this->planIdFor($donation->currency);

        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->post("{$this->apiBaseUrl()}/v1/billing/subscriptions", [
                'plan_id' => $planId,
                'custom_id' => $donation->uuid,
                'subscriber' => [
                    'name' => [
                        'given_name' => $donation->first_name,
                        'surname' => $donation->last_name,
                    ],
                    'email_address' => $donation->email,
                ],
                'plan' => [
                    'billing_cycles' => [
                        [
                            'sequence' => 1,
                            'pricing_scheme' => [
                                'fixed_price' => [
                                    'value' => Currency::toDecimalString($donation->total, $donation->currency),
                                    'currency_code' => $donation->currency,
                                ],
                            ],
                        ],
                    ],
                ],
                'application_context' => [
                    'brand_name' => 'Go Bike',
                    'user_action' => 'SUBSCRIBE_NOW',
                    'return_url' => route('donate.success', $donation),
                    'cancel_url' => route('donate.cancel', $donation),
                ],
            ]);

        if (! $response->successful()) {
            Log::error('PayPal subscription creation failed', ['response' => $response->json()]);

            $reason = $response->json('message')
                ?? $response->json('details.0.description')
                ?? $response->json('name')
                ?? 'Unexpected response from PayPal.';

            throw new RuntimeException("PayPal subscription creation failed: {$reason}");
        }

        $data = $response->json();

        $approvalUrl = collect($data['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if ($approvalUrl === null) {
            throw new RuntimeException('PayPal subscription approval URL not found.');
        }

        return [
            'approval_url' => $approvalUrl,
            'subscription_id' => $data['id'],
        ];
    }

    /**
     * The PayPal billing plan for monthly donations in this currency. A subscription can only
     * override a plan's price in the plan's own currency, so each currency needs its own plan.
     * Plans are created the first time they're needed and saved per PayPal mode, so switching
     * from sandbox to live never reuses a sandbox plan.
     */
    public function planIdFor(string $currency): string
    {
        $mode = (string) config('paypal.mode');
        $existing = fn () => PaypalDonationPlan::where('mode', $mode)->where('currency', $currency)->value('plan_id');

        if ($planId = $existing()) {
            return $planId;
        }

        // Two donors starting at once must not create two plans.
        return Cache::lock("paypal-donation-plan:{$mode}:{$currency}", 30)->block(20, function () use ($existing, $mode, $currency) {
            if ($planId = $existing()) {
                return $planId;
            }

            $productId = $this->productId($mode);

            $response = Http::withToken($this->getAccessToken())
                ->acceptJson()
                ->post("{$this->apiBaseUrl()}/v1/billing/plans", [
                    'product_id' => $productId,
                    'name' => "Go Bike Monthly Donation ({$currency})",
                    'description' => 'Charges the donor-selected amount every month',
                    'billing_cycles' => [
                        [
                            'frequency' => ['interval_unit' => 'MONTH', 'interval_count' => 1],
                            'tenure_type' => 'REGULAR',
                            'sequence' => 1,
                            'total_cycles' => 0, // runs until cancelled
                            'pricing_scheme' => [
                                'fixed_price' => [
                                    // Placeholder; each subscription sets the donor's amount.
                                    'value' => Currency::toDecimalString(1, $currency),
                                    'currency_code' => $currency,
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

            if (! $response->successful()) {
                Log::error('PayPal plan creation failed', ['currency' => $currency, 'response' => $response->json()]);

                throw new RuntimeException("PayPal plan creation failed for {$currency}: ".($response->json('message') ?? $response->body()));
            }

            return PaypalDonationPlan::create([
                'mode' => $mode,
                'currency' => $currency,
                'product_id' => $productId,
                'plan_id' => $response->json('id'),
            ])->plan_id;
        });
    }

    /** The PayPal catalog product the donation plans belong to: one per mode, created once. */
    private function productId(string $mode): string
    {
        $productId = config('donation.paypal.product_id')
            ?: PaypalDonationPlan::where('mode', $mode)->value('product_id');

        if ($productId) {
            return $productId;
        }

        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->post("{$this->apiBaseUrl()}/v1/catalogs/products", [
                'name' => 'Go Bike Monthly Donation',
                'description' => 'Recurring monthly donation to Go Bike',
                'type' => 'SERVICE',
            ]);

        if (! $response->successful()) {
            Log::error('PayPal product creation failed', ['response' => $response->json()]);

            throw new RuntimeException('PayPal product creation failed: '.($response->json('message') ?? $response->body()));
        }

        return $response->json('id');
    }

    /** Names the variables for the current mode (sandbox or live) so the error points at the right ones. */
    private function ensureConfigured(): void
    {
        $mode = config('paypal.mode');

        if ((string) config("paypal.{$mode}.client_id") === '') {
            $prefix = 'PAYPAL_'.strtoupper((string) $mode);

            throw new RuntimeException("PayPal is not configured. Add {$prefix}_CLIENT_ID and {$prefix}_CLIENT_SECRET to your .env file.");
        }
    }

    private function apiBaseUrl(): string
    {
        return config('paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function getAccessToken(): string
    {
        $mode = config('paypal.mode');
        $clientId = config("paypal.{$mode}.client_id");
        $clientSecret = config("paypal.{$mode}.client_secret");

        $response = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post("{$this->apiBaseUrl()}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to authenticate with PayPal: '.$response->body());
        }

        return $response->json('access_token');
    }



    /**
     * @return array<string, mixed>
     */
    public function getSubscriptionDetails(string $subscriptionId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get("{$this->apiBaseUrl()}/v1/billing/subscriptions/{$subscriptionId}");

        if (! $response->successful()) {
            throw new RuntimeException('Failed to retrieve PayPal subscription: '.$response->body());
        }

        return $response->json();
    }

    public function isSubscriptionActive(array $subscription): bool
    {
        return ($subscription['status'] ?? '') === 'ACTIVE';
    }

    /**
     * Stop a monthly donation. PayPal will not charge it again.
     * A subscription that is already cancelled counts as success.
     */
    public function cancelSubscription(string $subscriptionId, string $reason): void
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->post("{$this->apiBaseUrl()}/v1/billing/subscriptions/{$subscriptionId}/cancel", [
                'reason' => mb_substr($reason, 0, 128),
            ]);

        if ($response->successful()) {
            return;
        }

        $issue = $response->json('details.0.issue');

        if ($response->status() === 422 && $issue === 'SUBSCRIPTION_STATUS_INVALID') {
            return; // already cancelled or expired
        }

        Log::error('PayPal subscription cancellation failed', ['subscription_id' => $subscriptionId, 'response' => $response->json()]);

        throw new RuntimeException('PayPal subscription cancellation failed: '.($response->json('message') ?? $response->body()));
    }

    /**
     * Could PayPal still charge this subscription? Used before deleting a donation record.
     *
     * @return 'billing'|'stopped'|'not_found'
     *
     * @throws RuntimeException when PayPal can't be reached or answers unexpectedly
     */
    public function subscriptionBillingState(string $subscriptionId): string
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get("{$this->apiBaseUrl()}/v1/billing/subscriptions/{$subscriptionId}");

        if ($response->status() === 404) {
            return 'not_found';
        }

        if (! $response->successful()) {
            throw new RuntimeException('Could not check the PayPal subscription: '.$response->status());
        }

        return in_array($response->json('status'), ['ACTIVE', 'SUSPENDED', 'APPROVED'], true) ? 'billing' : 'stopped';
    }

    /** Map PayPal's subscription status to ours (active, suspended, cancelled, expired), or null if still pending. */
    public function localSubscriptionStatus(array $subscription): ?string
    {
        return match ($subscription['status'] ?? '') {
            'ACTIVE' => 'active',
            'SUSPENDED' => 'suspended',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'expired',
            default => null,
        };
    }


    
    /**
     * @return array<string, mixed>
     */
    public function captureOrder(string $orderId): array
    {
        return $this->client()->capturePaymentOrder($orderId);
    }

    public function isCaptureSuccessful(array $response): bool
    {
        return ($response['status'] ?? '') === 'COMPLETED';
    }


    
    public function verifyWebhookLocally(array $headers, string $rawBody): bool
    {
        $webhookId = (string) config('paypal.webhook_id');

        // No webhook ID configured means we cannot verify the sender, so reject everything.
        if ($webhookId === '') {
            return false;
        }

        $normalized = array_change_key_case($headers, CASE_LOWER);

        // Reject anything missing PayPal's signature headers before making any outside call.
        foreach ([
            'paypal-transmission-id',
            'paypal-transmission-time',
            'paypal-transmission-sig',
            'paypal-auth-algo',
            'paypal-cert-url',
        ] as $required) {
            if (blank($normalized[$required] ?? null)) {
                return false;
            }
        }

        // The signing certificate must be hosted by PayPal, never at an address the sender picked.
        $certUrl = (string) $normalized['paypal-cert-url'];
        $host = parse_url($certUrl, PHP_URL_HOST);

        if (parse_url($certUrl, PHP_URL_SCHEME) !== 'https'
            || ! is_string($host)
            || ! preg_match('/(^|\.)paypal\.com$/i', $host)) {
            return false;
        }

        return $this->client()->verifyWebHookLocally($headers, $webhookId, $rawBody);
    }  


}
