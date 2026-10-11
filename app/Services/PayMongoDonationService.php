<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoDonationService
{
    private string $secretKey;

    private string $baseUrl = 'https://api.paymongo.com/v1';

    public function __construct()
    {
        $this->secretKey = (string) config('services.paymongo.secret_key');
    }

    /**
     * @return array{checkout_url: string, session_id: string}
     */
    public function createCheckoutSession(Donation $donation): array
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('PayMongo is not configured. Add PAYMONGO_SECRET_KEY to your .env file.');
        }

        $paymentMethodTypes = $donation->payment_method === 'gcash'
            ? ['gcash']
            : ['dob'];

        $amountCentavos = (int) round((float) $donation->amount_php * 100);

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->post("{$this->baseUrl}/checkout_sessions", [
                'data' => [
                    'attributes' => [
                        'billing' => [
                            'name' => $donation->donor_name,
                            'email' => $donation->email,
                            'phone' => $donation->phone,
                        ],
                        'line_items' => [
                            [
                                'name' => 'Go Bike Donation',
                                'amount' => $amountCentavos,
                                'currency' => 'PHP',
                                'quantity' => 1,
                            ],
                        ],
                        'payment_method_types' => $paymentMethodTypes,
                        'success_url' => route('donate.success', $donation),
                        'cancel_url' => route('donate.cancel', $donation),
                        'reference_number' => $donation->uuid,
                        'send_email_receipt' => true,
                        'description' => 'Go Bike donation from '.$donation->donor_name,
                        'metadata' => [
                            'donation_uuid' => $donation->uuid,
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'PayMongo checkout failed: '.($response->json('errors.0.detail') ?? $response->body())
            );
        }

        $data = $response->json('data');

        return [
            'checkout_url' => $data['attributes']['checkout_url'],
            'session_id' => $data['id'],
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): ?array
    {
        if ($this->secretKey === '') {
            return null;
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->get("{$this->baseUrl}/checkout_sessions/{$sessionId}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('data');
    }

    public function isSessionPaid(?array $session): bool
    {
        if ($session === null) {
            return false;
        }

        $payments = $session['attributes']['payments'] ?? [];

        foreach ($payments as $payment) {
            if (($payment['attributes']['status'] ?? '') === 'paid') {
                return true;
            }
        }

        return ($session['attributes']['payment_intent']['attributes']['status'] ?? '') === 'succeeded';
    }

    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $secret = (string) config('services.paymongo.webhook_secret');

        if ($secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? $parts['v0'] ?? $parts['te'] ?? $parts['li'] ?? '';

        if ($timestamp === '' || $signature === '') {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }
}
