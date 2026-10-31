<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutDonationRequest;
use App\Models\Donation;
use App\Services\PayMongoDonationService;
use App\Services\ExchangeRateService;
use App\Services\PayPalDonationService;
use App\Support\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
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
        ExchangeRateService $rates,
    ): JsonResponse {
        $validated = $request->validated();
        $currency = $validated['currency'];
        $amount = Currency::round((float) $validated['amount'], $currency);
        $platformFee = Currency::round((float) ($validated['platform_fee'] ?? 0), $currency);

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
            'currency' => $currency,
            'amount' => $amount,
            'platform_fee' => $platformFee,
            // USD equivalents for admin totals, and the PHP amount GCash / online banking charge.
            'amount_usd' => round($rates->convert($amount, $currency, 'USD'), 2),
            'platform_fee_usd' => round($rates->convert($platformFee, $currency, 'USD'), 2),
            'amount_php' => round($rates->convert($amount + $platformFee, $currency, 'PHP'), 2),
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
                Log::error('Donation checkout failed', [
                   'donation_id' => $donation->id,
                   'method' => $donation->payment_method,
                   'error' => $e->getMessage(),
                ]);

                $donation->update(['status' => 'failed']);

                return response()->json([
                   'message' => 'We could not start the payment right now. Please try again in a moment.',
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
                    $donation->update(['status' => 'paid', 'subscription_status' => 'active']);
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

    /**
     * Private page where a donor manages their monthly donation (link shown on the thank-you screen).
     */
    public function manageMonthly(Donation $donation, PayPalDonationService $paypal): View
    {
        abort_unless($donation->isMonthlySubscription(), 404);

        $nextChargeAt = null;

        // Refresh from PayPal so the page is right even if a webhook was missed (e.g. cancelled in PayPal).
        try {
            $subscription = $paypal->getSubscriptionDetails($donation->paypal_subscription_id);
            $status = $paypal->localSubscriptionStatus($subscription);

            if ($status && $status !== $donation->subscription_status) {
                $donation->update([
                    'subscription_status' => $status,
                    'cancelled_at' => $status === 'cancelled' ? ($donation->cancelled_at ?? now()) : $donation->cancelled_at,
                ]);
            }

            $next = $subscription['billing_info']['next_billing_time'] ?? null;
            $nextChargeAt = $next && $status === 'active' ? \Illuminate\Support\Carbon::parse($next) : null;
        } catch (Throwable $e) {
            Log::warning('Could not refresh PayPal subscription', ['donation_id' => $donation->id, 'error' => $e->getMessage()]);
        }

        return view('donate-monthly', [
            'donation' => $donation->fresh()->loadCount('payments'),
            'nextChargeAt' => $nextChargeAt,
        ]);
    }

    public function cancelMonthly(Donation $donation, PayPalDonationService $paypal): RedirectResponse
    {
        abort_unless($donation->isMonthlySubscription(), 404);

        if (! $donation->canCancelSubscription()) {
            return redirect()->route('donate.monthly', $donation);
        }

        try {
            $paypal->cancelSubscription($donation->paypal_subscription_id, 'Cancelled by the donor on the Go Bike website.');
        } catch (Throwable $e) {
            return redirect()->route('donate.monthly', $donation)->with(
                'error',
                'We could not cancel your monthly donation right now. Please try again in a few minutes, or cancel it in your PayPal account.'
            );
        }

        $donation->update(['subscription_status' => 'cancelled', 'cancelled_at' => now()]);

        return redirect()->route('donate.monthly', $donation)
            ->with('success', 'Your monthly donation has been cancelled. You will not be charged again.');
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
            Log::error('Donation retry failed', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('donate.cancel', $donation)
                ->with('error', 'Could not create a new payment session. Please try again in a moment.');
        }

            
    }
}
