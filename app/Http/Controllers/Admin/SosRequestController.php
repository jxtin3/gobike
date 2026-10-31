<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SosRequest;
use App\Services\Push\PushNotifier;
use App\Support\Barangays;
use App\Support\LiveMap;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                        ->orWhere('address', 'like', "%{$search}%")
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
            // The GoBiker sent to help (live map). Only they can then see and resolve it in the app.
            // Any active GoBiker may be sent: the nearest one often covers the next barangay.
            'gobiker_id' => ['nullable', 'integer', Rule::exists('users', 'id')
                ->where('role', 'GoBiker')
                ->where('status', 'Active')],
        ]);

        $newGobikerId = $data['gobiker_id'] ?? null;
        // Sending a different GoBiker to a request already being answered: hand it over to them.
        $reassigning = $sos->status === 'responding'
            && $newGobikerId !== null
            && $newGobikerId !== $sos->assigned_gobiker_id;

        // Another admin may have answered first: tell this one instead of going backwards.
        if (! $reassigning && ! $sos->canMoveTo($data['status'])) {
            $message = "This request is already marked \"{$sos->status_label}\".";

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'status' => $sos->status], 422)
                : back()->with('error', $message);
        }

        $sos->status = $data['status'];
        $sos->handled_by = $request->user()->id;
        if ($data['status'] === 'responding') {
            $sos->responded_at ??= now();
            // Leaving the picker empty must never drop the GoBiker already on their way.
            $sos->assigned_gobiker_id = $newGobikerId ?? $sos->assigned_gobiker_id;
        } else {
            $sos->responded_at ??= now();
            $sos->resolved_at = now();
        }
        $assignmentChanged = $sos->isDirty('assigned_gobiker_id');
        $sos->save();
        $push = app(PushNotifier::class);
        $push->sosStatusChanged($sos->load('user', 'assignedGobiker'));
        if ($sos->status === 'responding' && $sos->assignedGobiker && $assignmentChanged) {
            $push->sosAssigned($sos);
        }

        $success = match (true) {
            $data['status'] === 'resolved' => 'SOS request resolved.',
            $reassigning => ($sos->assignedGobiker?->name ?? 'The GoBiker').' is now responding to this request.',
            default => 'Marked as responding. The resident can see this in the app.',
        };

        return $request->expectsJson()
            ? response()->json(['status' => $sos->status, 'assigned_gobiker' => $sos->assignedGobiker?->name])
            : back()->with('success', $success);
    }

    /**
     * Residents' SOS requests for the live map: open ones, and ones resolved in the last 30 minutes
     * (shown green for a while). Only requests sent with a GPS position can be drawn.
     */
    public function mapPins()
    {
        $pins = SosRequest::with('user:id,name,mobile', 'assignedGobiker:id,name,barangay', 'resolvedBy:id,name,barangay')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(fn ($q) => $q->open()
                ->orWhere(fn ($q) => $q->where('status', 'resolved')->where('resolved_at', '>=', now()->subMinutes(30))))
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (SosRequest $sos) => [
                'id' => $sos->id,
                'status' => $sos->status,
                'resident' => $sos->user?->name ?? 'Resident',
                'mobile' => $sos->user?->mobile,
                'barangay' => $sos->barangay,
                'address' => $sos->address,
                'landmark' => $sos->landmark,
                'description' => $sos->description,
                'latitude' => $sos->latitude,
                'longitude' => $sos->longitude,
                'assigned_gobiker' => $sos->assignedGobiker?->name,
                'assigned_gobiker_barangay' => $sos->assignedGobiker?->barangay,
                'resolved_by' => $sos->resolvedBy?->name ?? ($sos->resolved_by_resident ? 'the resident' : ($sos->status === 'resolved' ? 'an admin' : null)),
                'created_at' => $sos->created_at?->toIso8601String(),
            ]);

        return response()->json($pins);
    }

    /**
     * GoBikers the admin can send to this request, online first and nearest first.
     *
     * Defaults to the GoBikers covering the request's own barangay. The admin can ask for another
     * barangay, or for all of them, to find someone nearer than anyone stationed on the spot.
     */
    public function gobikers(Request $request, SosRequest $sos)
    {
        $filter = $request->query('barangay', $sos->barangay);
        $barangay = ($filter === 'all' || $filter === null) ? null : (string) $filter;

        return response()->json([
            'barangay' => $sos->barangay,
            'barangays' => Barangays::ALL,
            'gobikers' => LiveMap::gobikersForAssignment($sos->latitude, $sos->longitude, $barangay),
        ]);
    }

    /** Deletes the request with its photo and video (e.g. a test or a duplicate). */
    public function destroy(SosRequest $sos)
    {
        $files = array_filter([$sos->image_path, $sos->video_path]);
        $who = $sos->user?->name ?? 'a resident';
        $sos->delete();
        if ($files) {
            Storage::disk(Media::disk())->delete($files);
        }

        return redirect()->route('admin.operations.sos.index')
            ->with('success', "SOS request from {$who} deleted.");
    }
}
