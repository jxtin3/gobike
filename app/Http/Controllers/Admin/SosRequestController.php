<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SosRequest;
use App\Services\Push\PushNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Residents' SOS requests: list, details, and moving them to "Responding" / "Resolved". */
class SosRequestController extends Controller
{
    private const TABS = ['open', 'sent', 'responding', 'resolved', 'all'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('status'), self::TABS, true) ? $request->query('status') : 'open';
        $search = trim((string) $request->query('search', ''));

        $requests = SosRequest::with('user:id,name,mobile')
            ->when($tab === 'open', fn ($q) => $q->open())
            ->when(in_array($tab, ['sent', 'responding', 'resolved'], true), fn ($q) => $q->where('status', $tab))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhere('barangay', 'like', "%{$search}%")
                        ->orWhere('landmark', 'like', "%{$search}%");
                });
            })
            // Waiting ones first, then newest.
            ->orderByRaw("CASE status WHEN 'sent' THEN 0 WHEN 'responding' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.sos.index', [
            'requests' => $requests,
            'tab' => $tab,
            'counts' => [
                'open' => SosRequest::open()->count(),
                'sent' => SosRequest::where('status', 'sent')->count(),
                'responding' => SosRequest::where('status', 'responding')->count(),
                'resolved' => SosRequest::where('status', 'resolved')->count(),
                'all' => SosRequest::count(),
            ],
        ]);
    }

    public function show(SosRequest $sos)
    {
        $sos->load('user', 'handler:id,name');

        return view('admin.operations.sos.show', [
            'sos' => $sos,
            'earlier' => SosRequest::where('user_id', $sos->user_id)
                ->whereKeyNot($sos->id)
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, SosRequest $sos)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['responding', 'resolved'])],
        ]);

        // Another admin may have answered first: tell this one instead of going backwards.
        if (! $sos->canMoveTo($data['status'])) {
            $message = "This request is already marked \"{$sos->status_label}\".";

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'status' => $sos->status], 422)
                : back()->with('error', $message);
        }

        $sos->status = $data['status'];
        $sos->handled_by = $request->user()->id;
        if ($data['status'] === 'responding') {
            $sos->responded_at = now();
        } else {
            $sos->responded_at ??= now();
            $sos->resolved_at = now();
        }
        $sos->save();
        app(PushNotifier::class)->sosStatusChanged($sos->load('user'));

        return $request->expectsJson()
            ? response()->json(['status' => $sos->status])
            : back()->with('success', $data['status'] === 'resolved' ? 'SOS request resolved.' : 'Marked as responding. The resident can see this in the app.');
    }
}
