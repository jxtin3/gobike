<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\PayPalDonationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    public function handle(Request $request, PayPalDonationService $paypal): Response
    {
        $rawBody = $request->getContent();
        $headers = collect($request->headers->all())
            ->map(fn ($values) => $values[0] ?? '')
            ->all();

        if (! $paypal->verifyWebhookLocally($headers, $rawBody)) {
            return response('Invalid signature', 400);
        }

        $payload = $request->json()->all();
        $resource = $payload['resource'] ?? [];

        match ($payload['event_type'] ?? '') {
            // One-time donations
            'PAYMENT.CAPTURE.COMPLETED' => $this->markPaidFromCapture($resource),
            'CHECKOUT.ORDER.APPROVED' => $this->captureAndMarkPaid($resource, $paypal),

            // Monthly donations (subscriptions); resource.id is the subscription id
            'BILLING.SUBSCRIPTION.ACTIVATED',
            'BILLING.SUBSCRIPTION.RE-ACTIVATED' => $this->updateSubscription($resource['id'] ?? null, 'active'),
            'BILLING.SUBSCRIPTION.SUSPENDED' => $this->updateSubscription($resource['id'] ?? null, 'suspended'),
            'BILLING.SUBSCRIPTION.CANCELLED' => $this->updateSubscription($resource['id'] ?? null, 'cancelled'),
            'BILLING.SUBSCRIPTION.EXPIRED' => $this->updateSubscription($resource['id'] ?? null, 'expired'),
            'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => Log::warning('PayPal monthly donation payment failed', ['subscription_id' => $resource['id'] ?? null]),

            // Each monthly charge; resource.billing_agreement_id is the subscription id
            'PAYMENT.SALE.COMPLETED' => $this->recordSubscriptionPayment($resource),

            default => null,
        };

        return response('OK', 200);
    }

    private function markPaidFromCapture(array $resource): void
    {
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if (! $orderId) {
            return;
        }

        $donation = Donation::where('paypal_order_id', $orderId)->first();

        if ($donation && $donation->status !== 'paid') {
            $donation->update(['status' => 'paid']);
        }
    }

    private function updateSubscription(?string $subscriptionId, string $status): void
    {
        $donation = $subscriptionId ? Donation::where('paypal_subscription_id', $subscriptionId)->first() : null;

        if (! $donation) {
            return;
        }

        $updates = ['subscription_status' => $status];

        if ($status === 'active') {
            $updates['status'] = 'paid';
        }

        if ($status === 'cancelled' && ! $donation->cancelled_at) {
            $updates['cancelled_at'] = now();
        }

        $donation->update($updates);
    }

    private function recordSubscriptionPayment(array $resource): void
    {
        $subscriptionId = $resource['billing_agreement_id'] ?? null;
        $saleId = $resource['id'] ?? null;
        $donation = $subscriptionId ? Donation::where('paypal_subscription_id', $subscriptionId)->first() : null;

        if (! $donation || ! $saleId) {
            return;
        }

        $donation->payments()->firstOrCreate(
            ['gateway_payment_id' => $saleId],
            [
                'gateway' => 'paypal',
                'amount' => $resource['amount']['total'] ?? $donation->total_usd,
                'currency' => $resource['amount']['currency'] ?? 'USD',
                'paid_at' => isset($resource['create_time']) ? Carbon::parse($resource['create_time']) : now(),
            ],
        );

        // A successful charge means the subscription is running (unless it was cancelled since).
        $donation->update([
            'status' => 'paid',
            'subscription_status' => in_array($donation->subscription_status, ['cancelled', 'expired'], true)
                ? $donation->subscription_status
                : 'active',
        ]);
    }

    private function captureAndMarkPaid(array $resource, PayPalDonationService $paypal): void
    {
        $referenceId = $resource['purchase_units'][0]['reference_id'] ?? null;
        $orderId = $resource['id'] ?? null;

        $donation = null;

        if ($referenceId) {
            $donation = Donation::where('uuid', $referenceId)->first();
        }

        if (! $donation && $orderId) {
            $donation = Donation::where('paypal_order_id', $orderId)->first();
        }

        if (! $donation || $donation->status === 'paid' || ! $orderId) {
            return;
        }

        try {
            $capture = $paypal->captureOrder($orderId);
        } catch (\Throwable) {
            return;
        }

        if ($paypal->isCaptureSuccessful($capture)) {
            $donation->update(['status' => 'paid']);
        }
    }
}
