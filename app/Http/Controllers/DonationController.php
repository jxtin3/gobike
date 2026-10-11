<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutDonationRequest;
use App\Models\Donation;
use App\Services\PayMongoDonationService;
use App\Services\PayPalDonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DonationController extends Controller
{
    public function show(): View
    {
        return view('donate');
    }

    public function checkout(
        CheckoutDonationRequest $request,
        PayPalDonationService $paypal,
        PayMongoDonationService $paymongo,
    ): JsonResponse {
        $validated = $request->validated();
        $amountUsd = round((float) $validated['amount'], 2);
        $platformFee = round((float) ($validated['platform_fee'] ?? 0), 2);
        $usdToPhp = (float) config('donation.usd_to_php');

        $donation = Donation::create([
            'donor_type' => $validated['donor_type'],
            'org_name' => $validated['org_name'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address1' => $validated['address1'],
            'address2' => $validated['address2'] ?? null,
            'city' => $validated['city'],
            'postcode' => $validated['postcode'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'other_country' => $validated['other_country'] ?? null,
            'amount_usd' => $amountUsd,
            'amount_php' => round(($amountUsd + $platformFee) * $usdToPhp, 2),
            'platform_fee_usd' => $platformFee,
            'frequency' => $validated['frequency'],
            'payment_method' => $validated['payment_method'],
            'status' => 'pending',
            'receipt_email' => $validated['receipt_email'] ?? null,
        ]);

        try {
            if ($donation->payment_method === 'paypal' && $donation->frequency === 'monthly') {
                $result = $paypal->createSubscription($donation);
                $donation->update(['paypal_subscription_id' => $result['subscription_id']]);
                $redirectUrl = $result['approval_url'];
            } elseif ($donation->payment_method === 'paypal') {
                $result = $paypal->createOrder($donation);
                $donation->update(['paypal_order_id' => $result['order_id']]);
                $redirectUrl = $result['approval_url'];
            } else {
                $result = $paymongo->createCheckoutSession($donation);
                $donation->update(['paymongo_session_id' => $result['session_id']]);
                $redirectUrl = $result['checkout_url'];
            }
        } catch (\Throwable $e) {
            $donation->update(['status' => 'failed']);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'redirect_url' => $redirectUrl,
        ]);
    }

    public function success(
        Request $request,
        Donation $donation,
        PayPalDonationService $paypal,
        PayMongoDonationService $paymongo,
    ): View|RedirectResponse {
    if ($donation->status !== 'paid') {
        if ($donation->payment_method === 'paypal' && $donation->frequency === 'monthly' && $request->filled('subscription_id') && $donation->paypal_subscription_id) {
            try {
                $subscription = $paypal->getSubscriptionDetails($donation->paypal_subscription_id);

                if ($paypal->isSubscriptionActive($subscription)) {
                    $donation->update(['status' => 'paid']);
                }
            } catch (\Throwable) {
                return redirect()
                    ->route('donate.cancel', $donation)
                    ->with('error', 'Subscription could not be confirmed. Please try again.');
            }
        } elseif ($donation->payment_method === 'paypal' && $request->filled('token') && $donation->paypal_order_id) {
            try {
            $capture = $paypal->captureOrder($donation->paypal_order_id);

            if ($paypal->isCaptureSuccessful($capture)) {
                    $donation->update(['status' => 'paid']);
                }
            } catch (\Throwable) {
                return redirect()
                    ->route('donate.cancel', $donation)
                    ->with('error', 'Payment could not be confirmed. Please try again.');
            }
        } elseif ($donation->paymongo_session_id) {
                $session = $paymongo->retrieveCheckoutSession($donation->paymongo_session_id);

                if ($paymongo->isSessionPaid($session)) {
                    $paymentId = $session['attributes']['payments'][0]['id'] ?? null;
                    $donation->markPaid($paymentId);
                }
            }
        }

        if ($donation->fresh()->status !== 'paid') {
            return redirect()
                ->route('donate.cancel', $donation)
                ->with('error', 'Payment is still pending or was not completed.');
        }

        return view('donate', [
            'paidDonation' => $donation->fresh(),
        ]);
    }

    public function cancel(Donation $donation): View
    {
        // For PayMongo, landing on cancel often means the token expired —
        // keep it as 'pending' so the user can retry via the retry route.
        if ($donation->status === 'pending' && ! $donation->paymongo_session_id) {
            $donation->update(['status' => 'cancelled']);
        }

        return view('donate', [
            'cancelledDonation' => $donation,
        ]);
    }

    /**
     * Retry a PayMongo payment by creating a fresh checkout session.
     * Used when the previous session token expired ("Access token is invalid or expired").
     */
    public function retry(
        Donation $donation,
        PayMongoDonationService $paymongo,
    ): RedirectResponse {
        // Only allow retry for pending PayMongo donations
        if (! in_array($donation->status, ['pending', 'cancelled'], true) || ! $donation->paymongo_session_id) {
            return redirect()->route('donate')->with('error', 'This donation cannot be retried.');
        }

        try {
            // Create a brand-new checkout session with a fresh token
            $result = $paymongo->createCheckoutSession($donation);

            $donation->update([
                'paymongo_session_id' => $result['session_id'],
                'status' => 'pending',
            ]);

            return redirect()->away($result['checkout_url']);
        } catch (Throwable $e) {
            return redirect()
                ->route('donate.cancel', $donation)
                ->with('error', 'Could not create a new payment session: '.$e->getMessage());
        }
    }
}
