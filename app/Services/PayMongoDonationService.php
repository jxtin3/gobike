<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\ReplicationRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayMongo checkout (GCash and online banking) for donations and for
 * Go Biker's Training fees paid early from the Replicate Go Bike page.
 */
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
        $paymentMethodTypes = $donation->payment_method === 'gcash'
            ? ['gcash']
            : ['dob'];

        return $this->createSession([
            'billing' => [
                'name' => $donation->donor_name,
                'email' => $donation->email,
                'phone' => $donation->phone,
            ],
            'line_items' => [
                [
                    'name' => 'Go Bike Donation',
                    'amount' => (int) round((float) $donation->amount_php * 100),
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
        ]);
    }

    /**
     * Checkout for the Go Biker's Training fee of a chapter replication inquiry.
     *
     * @return array{checkout_url: string, session_id: string}
     */
    public function createTrainingCheckoutSession(ReplicationRequest $inquiry): array
    {
        return $this->createSession([
            'billing' => [
                'name' => $inquiry->contact_name,
                'email' => $inquiry->email,
                'phone' => $inquiry->phone,
            ],
            'line_items' => [
                [
                    'name' => "Go Biker's Training ({$inquiry->trainees} trainees)",
                    'amount' => (int) round((float) $inquiry->amount_php * 100),
                    'currency' => 'PHP',
                    'quantity' => 1,
                ],
            ],
            'payment_method_types' => config('replication.payment_methods'),
            'success_url' => route('replicate.inquiry.show', ['replicationRequest' => $inquiry, 'payment' => 'success']),
            'cancel_url' => route('replicate.inquiry.show', ['replicationRequest' => $inquiry, 'payment' => 'cancelled']),
            'reference_number' => $inquiry->reference,
            'send_email_receipt' => true,
            'description' => "Go Biker's Training for {$inquiry->organization} ({$inquiry->reference})",
            'metadata' => [
                'replication_uuid' => $inquiry->uuid,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes  PayMongo checkout session attributes
     * @return array{checkout_url: string, session_id: string}
     */
    private function createSession(array $attributes): array
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('PayMongo is not configured. Add PAYMONGO_SECRET_KEY to your .env file.');
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->post("{$this->baseUrl}/checkout_sessions", [
                'data' => ['attributes' => $attributes],
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

        // PayMongo sends "t=…,te=<sig>,li=" in test mode and "t=…,te=,li=<sig>" in live mode,
        // so take the first field that actually has a value.
        $timestamp = $parts['t'] ?? '';
        $signature = collect(['li', 'te', 'v1', 'v0'])
            ->map(fn (string $key) => $parts[$key] ?? '')
            ->first(fn (string $value) => $value !== '') ?? '';

        if ($timestamp === '' || $signature === '') {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }
}
