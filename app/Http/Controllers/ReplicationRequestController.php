<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReplicationRequest;
use App\Models\ReplicationRequest;
use App\Services\PayMongoDonationService;
use App\Services\PayPalDonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Chapter replication inquiries from the Replicate Go Bike page, with the option
 * to pay the Go Biker's Training fee early online: PayMongo (GCash or online banking) while
 * PayMongo is switched on, otherwise PayPal in pesos (config/payments.php).
 */
class ReplicationRequestController extends Controller
{
    public function store(StoreReplicationRequest $request, PayMongoDonationService $paymongo, PayPalDonationService $paypal): RedirectResponse
    {
        $data = $request->validated();

        // The fee is always calculated here, never taken from the browser.
        $inquiry = ReplicationRequest::create([
            ...Arr::except($data, ['payment', 'agree']),
            'amount_php' => ReplicationRequest::feeFor((int) $data['trainees']),
        ]);

        if ($data['payment'] === 'later') {
            return redirect()->to(route('replicate').'#inquire')->with(
                'replication_success',
                "Thank you! We received your inquiry (reference {$inquiry->reference}). Our team will contact you within 48 hours."
            );
        }

        return $this->startCheckout($inquiry, $paymongo, $paypal, fromForm: true);
    }

    /**
     * Status page for one inquiry: hand-off to PayMongo or PayPal, payment confirmation, or "pay again".
     */
    public function show(
        Request $request,
        ReplicationRequest $replicationRequest,
        PayMongoDonationService $paymongo,
        PayPalDonationService $paypal,
    ): View {
        $checkoutUrl = $request->session()->get('checkout_url');
        $token = $request->query('token');

        if (! $checkoutUrl
            && $replicationRequest->payment_status === 'pending'
            && $replicationRequest->paypal_order_id
            && is_string($token)
            && hash_equals($replicationRequest->paypal_order_id, $token)) {
            // Coming back from PayPal: capture the approved order. If it fails, the inquiry stays
            // pending so "pay again" still shows.
            $this->capturePayPal($replicationRequest, $paypal);
        } elseif (! $checkoutUrl && $replicationRequest->payment_status === 'pending' && $replicationRequest->paymongo_session_id) {
            // Coming back from PayMongo: confirm the payment directly in case the webhook hasn't arrived yet.
            $session = $paymongo->retrieveCheckoutSession($replicationRequest->paymongo_session_id);

            if ($paymongo->isSessionPaid($session)) {
                $replicationRequest->markPaid($session['attributes']['payments'][0]['id'] ?? null);
            }
        }

        return view('what-we-do.replicate-inquiry', [
            'inquiry' => $replicationRequest->fresh(),
            'checkoutUrl' => $checkoutUrl,
            'cancelled' => $request->query('payment') === 'cancelled',
        ]);
    }

    /**
     * Start (or restart) the online payment for an inquiry.
     */
    public function pay(ReplicationRequest $replicationRequest, PayMongoDonationService $paymongo, PayPalDonationService $paypal): RedirectResponse
    {
        if (! config('payments.online') || ! $replicationRequest->canPayOnline()) {
            return redirect()->route('replicate.inquiry.show', $replicationRequest);
        }

        return $this->startCheckout($replicationRequest, $paymongo, $paypal, fromForm: false);
    }

    private function startCheckout(
        ReplicationRequest $inquiry,
        PayMongoDonationService $paymongo,
        PayPalDonationService $paypal,
        bool $fromForm,
    ): RedirectResponse {
        $usePayMongo = (bool) config('payments.paymongo');

        try {
            if ($usePayMongo) {
                $result = $paymongo->createTrainingCheckoutSession($inquiry);
                $updates = ['paymongo_session_id' => $result['session_id']];
                $checkoutUrl = $result['checkout_url'];
            } else {
                $result = $paypal->createTrainingOrder($inquiry);
                $updates = ['paypal_order_id' => $result['order_id']];
                $checkoutUrl = $result['approval_url'];
            }
        } catch (Throwable $e) {
            Log::error('Training payment checkout failed', [
                'replication_request_id' => $inquiry->id,
                'gateway' => $usePayMongo ? 'paymongo' : 'paypal',
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('replicate.inquiry.show', $inquiry)->with(
                'error',
                'Your inquiry is saved, but we could not start the online payment right now. Please try again in a moment, or pay by check later.'
            );
        }

        $inquiry->update([...$updates, 'payment_status' => 'pending']);

        // Our Content-Security-Policy only lets forms submit to this site, and browsers apply that to
        // the redirect after a form post too, so a submitted form hands off to the gateway from our own page.
        return $fromForm
            ? redirect()->route('replicate.inquiry.show', $inquiry)->with('checkout_url', $checkoutUrl)
            : redirect()->away($checkoutUrl);
    }

    private function capturePayPal(ReplicationRequest $inquiry, PayPalDonationService $paypal): void
    {
        try {
            $capture = $paypal->captureOrder($inquiry->paypal_order_id);
        } catch (Throwable $e) {
            Log::error('Training fee PayPal capture failed', [
                'replication_request_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($paypal->isCaptureSuccessful($capture)) {
            $inquiry->markPaid(PayPalDonationService::captureIdFrom($capture), 'paypal');

            return;
        }

        Log::error('Training fee PayPal capture was not completed', [
            'replication_request_id' => $inquiry->id,
            'status' => $capture['status'] ?? null,
            'response' => $capture,
        ]);
    }
}
