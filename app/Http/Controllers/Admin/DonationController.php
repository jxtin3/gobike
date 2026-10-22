<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\PayPalDonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $query = Donation::latest()
            ->withCount('payments')
            ->withSum('payments', 'amount');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('org_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->input('method')) {
            $query->where('payment_method', $method);
        }

        if ($request->input('frequency') === 'monthly') {
            $query->where('frequency', 'monthly');
        }

        $donations = $query->paginate(20)->withQueryString();

        return view('admin.operations.donations.index', [
            'donations' => $donations,
            'totalPaid' => $this->totalCollected(),
            'totalCount' => Donation::where('status', 'paid')->count(),
            'pendingCount' => Donation::where('status', 'pending')->count(),
            'monthlyCount' => Donation::where('frequency', 'monthly')
                ->where('status', 'paid')
                ->whereNotNull('paypal_subscription_id')
                ->where(fn ($q) => $q->whereNull('subscription_status')->orWhere('subscription_status', 'active'))
                ->count(),
        ]);
    }

    /**
     * Donations received in USD, without platform fees. Each PayPal charge of a monthly donation
     * (the first one included) is recorded as a donation payment, so those count once per charge.
     */
    private function totalCollected(): float
    {
        $oneTime = (float) Donation::where('status', 'paid')->where('frequency', '!=', 'monthly')->sum('amount_usd');

        $monthly = Donation::where('status', 'paid')->where('frequency', 'monthly')
            ->withCount('payments')
            ->get(['id', 'amount_usd'])
            ->sum(fn (Donation $donation) => (float) $donation->amount_usd * max(1, $donation->payments_count));

        return $oneTime + $monthly;
    }

    /**
     * Stop a donor's monthly donation in PayPal (e.g. when they ask us by email).
     */
    public function cancelSubscription(Donation $donation, PayPalDonationService $paypal): RedirectResponse
    {
        if (! $donation->canCancelSubscription()) {
            return back()->with('error', 'This donation is not an active monthly donation.');
        }

        try {
            $paypal->cancelSubscription($donation->paypal_subscription_id, 'Cancelled by Go Bike at the donor\'s request.');
        } catch (Throwable) {
            return back()->with('error', 'PayPal could not cancel this monthly donation. Please try again, or cancel it in the PayPal business dashboard.');
        }

        $donation->update(['subscription_status' => 'cancelled', 'cancelled_at' => now()]);

        return back()->with('success', "Monthly donation from {$donation->donor_name} cancelled. PayPal will not charge it again.");
    }

    /**
     * Delete a donation record. A monthly donation that PayPal could still charge must be cancelled
     * first, otherwise the donor would keep being charged with no record on our side.
     */
    public function destroy(Donation $donation, PayPalDonationService $paypal): RedirectResponse
    {
        if ($donation->canCancelSubscription()) {
            try {
                $state = $paypal->subscriptionBillingState($donation->paypal_subscription_id);
            } catch (Throwable) {
                return back()->with('error', 'This is a monthly donation and PayPal could not be reached to check it. Please try again in a moment.');
            }

            if ($state === 'billing') {
                return back()->with('error', 'This monthly donation is still active in PayPal. Cancel the monthly donation first, so the donor is not charged again, then delete it.');
            }
        }

        $name = $donation->donor_name;
        $donation->delete();

        return back()->with('success', "Donation from {$name} deleted.");
    }
}
