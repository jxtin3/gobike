<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReplicationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Chapter replication inquiries from the Replicate Go Bike page, and their training payments.
 */
class ReplicationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = ReplicationRequest::latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('organization', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('municipality', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($payment = $request->input('payment')) {
            $query->where('payment_status', $payment);
        }

        return view('admin.operations.chapter-inquiries.index', [
            'inquiries' => $query->paginate(20)->withQueryString(),
            'totalCount' => ReplicationRequest::count(),
            'pendingCount' => ReplicationRequest::where('status', 'pending')->count(),
            'paidCount' => ReplicationRequest::where('payment_status', 'paid')->count(),
            'paidTotal' => (float) ReplicationRequest::where('payment_status', 'paid')->sum('amount_php'),
        ]);
    }

    public function show(ReplicationRequest $replicationRequest): View
    {
        return view('admin.operations.chapter-inquiries.show', [
            'inquiry' => $replicationRequest,
        ]);
    }

    /**
     * Update the inquiry status, or record a payment made by check / a refund.
     */
    public function update(Request $request, ReplicationRequest $replicationRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(ReplicationRequest::STATUSES)],
            'payment_status' => ['sometimes', 'required', Rule::in(ReplicationRequest::PAYMENT_STATUSES)],
        ]);

        if (($data['payment_status'] ?? null) === 'paid' && ! $replicationRequest->paid_at) {
            $data['paid_at'] = now();
        }

        $replicationRequest->update($data);

        return back()->with('success', 'Inquiry updated.');
    }
}
