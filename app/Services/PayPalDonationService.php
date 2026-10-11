<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Donation;
use RuntimeException;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalDonationService
{
    private PayPalClient $client;

    public function __construct()
    {
        $this->client = new PayPalClient;
        $this->client->setApiCredentials(config('paypal'));
        $this->client->getAccessToken();
    }

    /**
     * @return array{approval_url: string, order_id: string}
     */
    public function createOrder(Donation $donation): array
    {
        $clientId = config('paypal.'.config('paypal.mode').'.client_id');

        if ($clientId === '') {
            throw new RuntimeException('PayPal is not configured. Add PAYPAL_SANDBOX_CLIENT_ID and PAYPAL_SANDBOX_CLIENT_SECRET to your .env file.');
        }

        $response = $this->client->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $donation->uuid,
                    'description' => 'Go Bike Donation',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($donation->total_usd, 2, '.', ''),
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
        $clientId = config('paypal.'.config('paypal.mode').'.client_id');

        if ($clientId === '') {
            throw new RuntimeException('PayPal is not configured. Add PAYPAL_SANDBOX_CLIENT_ID and PAYPAL_SANDBOX_CLIENT_SECRET to your .env file.');
        }

        $planId = config('donation.paypal.plan_id');

        if (! $planId) {
            throw new RuntimeException('PayPal donation plan is not configured. Run `php artisan paypal:setup-donation-plan` and add PAYPAL_DONATION_PLAN_ID to your .env file.');
        }

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
                                    'value' => number_format($donation->total_usd, 2, '.', ''),
                                    'currency_code' => 'USD',
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
     * @return array<string, mixed>
     */
    public function captureOrder(string $orderId): array
    {
        return $this->client->capturePaymentOrder($orderId);
    }

    public function isCaptureSuccessful(array $response): bool
    {
        return ($response['status'] ?? '') === 'COMPLETED';
    }

    public function verifyWebhookLocally(array $headers, string $rawBody): bool
    {
        $webhookId = (string) config('paypal.webhook_id');

        // No webhook ID configured ==  can't verify the sender, so reject everything.
        if ($webhookId === '') {
            return false;
        }

        return $this->client->verifyWebHookLocally($headers, $webhookId, $rawBody);
    }
}
