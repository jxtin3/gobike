<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReplicationRequest;
use App\Models\ReplicationRequest;
use App\Services\PayMongoDonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Chapter replication inquiries from the Replicate Go Bike page, with the option
 * to pay the Go Biker's Training fee early online (PayMongo: GCash or online banking).
 */
class ReplicationRequestController extends Controller
{
    public function store(StoreReplicationRequest $request, PayMongoDonationService $paymongo): RedirectResponse
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

        return $this->startCheckout($inquiry, $paymongo, fromForm: true);
    }

    /**
     * Status page for one inquiry: hand-off to PayMongo, payment confirmation, or "pay again".
     */
    public function show(Request $request, ReplicationRequest $replicationRequest, PayMongoDonationService $paymongo): View
    {
        $checkoutUrl = $request->session()->get('checkout_url');

        // Coming back from PayMongo: confirm the payment directly in case the webhook hasn't arrived yet.
        if (! $checkoutUrl && $replicationRequest->payment_status === 'pending' && $replicationRequest->paymongo_session_id) {
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
    public function pay(ReplicationRequest $replicationRequest, PayMongoDonationService $paymongo): RedirectResponse
    {
        if (! $replicationRequest->canPayOnline()) {
            return redirect()->route('replicate.inquiry.show', $replicationRequest);
        }

        return $this->startCheckout($replicationRequest, $paymongo, fromForm: false);
    }

    private function startCheckout(ReplicationRequest $inquiry, PayMongoDonationService $paymongo, bool $fromForm): RedirectResponse
    {
        try {
            $result = $paymongo->createTrainingCheckoutSession($inquiry);
        } catch (Throwable $e) {
            Log::error('Training payment checkout failed', [
                'replication_request_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('replicate.inquiry.show', $inquiry)->with(
                'error',
                'Your inquiry is saved, but we could not start the online payment right now. Please try again in a moment, or pay by check later.'
            );
        }

        $inquiry->update([
            'paymongo_session_id' => $result['session_id'],
            'payment_status' => 'pending',
        ]);

        // Our Content-Security-Policy only lets forms submit to this site, and browsers apply that to
        // the redirect after a form post too, so a submitted form hands off to PayMongo from our own page.
        return $fromForm
            ? redirect()->route('replicate.inquiry.show', $inquiry)->with('checkout_url', $result['checkout_url'])
            : redirect()->away($result['checkout_url']);
    }
}
