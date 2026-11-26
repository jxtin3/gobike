<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\SosRequest;
use App\Models\SosResponder;
use App\Models\User;
use App\Services\Push\PushNotifier;
use App\Support\GobikerEmergency;
use App\Support\LiveMap;
use App\Support\Media;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use League\Flysystem\Local\LocalFilesystemAdapter;

/** Residents' SOS requests: list, details, and moving them to "Responding" / "Resolved". */
class SosRequestController extends Controller
{
    private const TABS = ['open', 'sent', 'responding', 'declined', 'resolved', 'cancelled', 'all'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('status'), self::TABS, true) ? $request->query('status') : 'open';
        $search = trim((string) $request->query('search', ''));

        $requests = SosRequest::with('user:id,name,mobile')
            ->when($tab === 'open', fn ($q) => $q->open())
            ->when(in_array($tab, ['sent', 'responding', 'declined', 'resolved', 'cancelled'], true), fn ($q) => $q->where('status', $tab))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhere('barangay', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('landmark', 'like', "%{$search}%");
                });
            })
            // Waiting ones first, then declined (need reassigning), then the rest; newest within each.
            ->orderByRaw("CASE status WHEN 'sent' THEN 0 WHEN 'declined' THEN 1 WHEN 'responding' THEN 2 ELSE 3 END")
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
                'declined' => SosRequest::where('status', 'declined')->count(),
                'resolved' => SosRequest::where('status', 'resolved')->count(),
                'cancelled' => SosRequest::where('status', 'cancelled')->count(),
                'all' => SosRequest::count(),
            ],
        ]);
    }

    public function show(SosRequest $sos)
    {
        $sos->load(['user', 'handler:id,name', 'declinedBy:id,name', 'responders' => fn ($q) => $q->latest('assigned_at')->latest('id'), 'responders.gobiker:id,name,barangay']);

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
            // Any active GoBiker may be sent: the nearest one often covers the next barangay. They
            // must be online, though — a closed app cannot be told that someone needs help, and
            // the picker only offers online GoBikers, so this closes the same door to the API.
            'gobiker_id' => ['nullable', 'integer', Rule::exists('users', 'id')
                ->where('role', 'GoBiker')
                ->where('status', 'Active'),
                fn (string $attribute, mixed $value, Closure $fail) => LiveMap::isOnline((int) $value)
                    ? null
                    : $fail('That GoBiker is offline. Send one whose app is on and showing on the map.'),
                // A GoBiker's own emergency: someone else has to go.
                fn (string $attribute, mixed $value, Closure $fail) => (int) $value === $sos->user_id
                    ? $fail('This GoBiker is the one who needs help. Send someone else.')
                    : null,
            ],
        ]);

        $gobiker = isset($data['gobiker_id']) ? User::find((int) $data['gobiker_id']) : null;

        // Sending a GoBiker adds them to whoever is already on the way (or to a request the last
        // one turned down), so it may go to "responding" from any status that is still open.
        $sending = $gobiker !== null
            && $data['status'] === 'responding'
            && in_array($sos->status, ['sent', 'responding', 'declined'], true);

        // Another admin may have answered first: tell this one instead of going backwards.
        if (! $sending && ! $sos->canMoveTo($data['status'])) {
            $message = "This request is already marked \"{$sos->status_label}\".";

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'status' => $sos->status], 422)
                : back()->with('error', $message);
        }

        if ($sending && $sos->isRespondedBy($gobiker->id)) {
            throw ValidationException::withMessages([
                'gobiker_id' => "{$gobiker->name} is already responding to this request.",
            ]);
        }

        $othersOnTheWay = $sos->activeResponders()->exists();

        $sos->status = $data['status'];
        $sos->handled_by = $request->user()->id;
        $sos->responded_at ??= now();
        if ($data['status'] === 'resolved') {
            $sos->resolved_at = now();
        }

        if ($sending) {
            // A GoBiker who declined before is sent again: same row, back on the way, and alerted
            // again (assigned_at is new, which is how the app tells a new alert apart).
            SosResponder::updateOrCreate(
                ['sos_request_id' => $sos->id, 'gobiker_id' => $gobiker->id],
                [
                    'status' => SosResponder::RESPONDING,
                    'assigned_by' => $request->user()->id,
                    'assigned_at' => now(),
                    'declined_at' => null,
                    'decline_reason' => null,
                ],
            );
            $sos->syncAssignedGobiker();
        }
        $sos->save();

        GobikerEmergency::syncLocation($sos);
        $push = app(PushNotifier::class);
        $push->sosStatusChanged($sos->load('user', 'assignedGobiker'));
        if ($sending) {
            $push->sosAssigned($sos, $gobiker);
        }

        $success = match (true) {
            $data['status'] === 'resolved' => 'SOS request resolved.',
            $sending && $othersOnTheWay => "{$gobiker->name} is now also responding.",
            $sending => "Marked as responding. {$gobiker->name} was notified.",
            default => 'Marked as responding. The resident can see this in the app.',
        };

        return $request->expectsJson()
            ? response()->json([
                'message' => $success,
                'status' => $sos->status,
                'assigned_gobiker' => $sos->assignedGobiker?->name,
                'responders' => $sos->activeResponders()->with('gobiker:id,name')->get()
                    ->map(fn (SosResponder $r) => $r->gobiker?->name)->filter()->values(),
            ])
            : back()->with('success', $success);
    }

    /**
     * Residents' SOS requests for the live map: the open ones only. Resolved requests leave the
     * map, so what the admin sees is what still needs someone. Who resolved it, and when, stays
     * on the request's own page.
     *
     * Only requests sent with a GPS position can be drawn. The map asks for this about once a
     * second, so the columns are listed rather than selecting the whole row.
     */
    public function mapPins()
    {
        $pins = SosRequest::with([
            'user:id,name,mobile,photo_path,role',
            'assignedGobiker:id,name,barangay,photo_path',
            'declinedBy:id,name',
            'responders' => fn ($q) => $q->latest('assigned_at')->latest('id'),
            'responders.gobiker:id,name,barangay,photo_path',
        ])
            ->select([
                'id', 'user_id', 'status', 'barangay', 'landmark', 'description', 'severity', 'injured',
                'image_path', 'video_path',
                'latitude', 'longitude', 'location_accuracy', 'location_recorded_at',
                'assigned_gobiker_id', 'declined_by_gobiker_id', 'decline_reason', 'created_at',
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->open()
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (SosRequest $sos) => [
                'id' => $sos->id,
                'user_id' => $sos->user_id,
                'status' => $sos->status,
                // A GoBiker's own emergency (GobikerEmergency) is drawn and answered the same way.
                'from' => $sos->user?->role === 'GoBiker' ? 'gobiker' : 'resident',
                'resident' => $sos->user?->name ?? 'Resident',
                'mobile' => $sos->user?->mobile,
                'resident_photo' => Media::url($sos->user?->photo_path),
                'barangay' => $sos->barangay,
                'landmark' => $sos->landmark,
                'description' => $sos->description,
                // For the details card beside the map. Requests from older apps have neither.
                'severity' => $sos->severity,
                'severity_label' => $sos->severity_label,
                'injured' => $sos->injured,
                'injured_label' => $sos->injured_label,
                'image_url' => $sos->image_url,
                'video_url' => $sos->video_url,
                'created_at' => $sos->created_at?->toIso8601String(),
                'view_url' => route('admin.operations.sos.show', $sos),
                'latitude' => $sos->latitude,
                'longitude' => $sos->longitude,
                'accuracy' => $sos->location_accuracy,
                'position_at' => $sos->location_recorded_at?->toIso8601String(),
                'assigned_gobiker' => $sos->assignedGobiker?->name,
                'assigned_gobiker_barangay' => $sos->assignedGobiker?->barangay,
                'assigned_gobiker_photo' => Media::url($sos->assignedGobiker?->photo_path),
                // Set when the assigned GoBiker turned the request down: the admin must send
                // someone else, so the pin says who declined and why.
                'declined_by' => $sos->status === 'declined' ? $sos->declinedBy?->name : null,
                'decline_reason' => $sos->status === 'declined' ? $sos->decline_reason : null,
                // Every GoBiker sent, newest first; those who declined keep their reason.
                'responders' => $sos->responders->map(fn (SosResponder $r) => [
                    'id' => $r->gobiker_id,
                    'name' => $r->gobiker?->name,
                    'barangay' => $r->gobiker?->barangay,
                    'photo' => Media::url($r->gobiker?->photo_path),
                    'status' => $r->status,
                    'decline_reason' => $r->status === SosResponder::DECLINED ? $r->decline_reason : null,
                ])->values(),
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
            'barangays' => $this->barangayChoices($sos),
            // A GoBiker's own emergency: they cannot be sent to help themselves. Those already on
            // the way are left out too: sending them again would change nothing.
            'gobikers' => LiveMap::gobikersForAssignment(
                $sos->latitude,
                $sos->longitude,
                $barangay,
                array_map('intval', [$sos->user_id, ...$sos->activeResponders()->pluck('gobiker_id')->all()]),
            ),
        ]);
    }

    /**
     * Every barangay as {name, town}, for the picker's search. Many names repeat from town to town
     * ("Poblacion"), so the town is what tells them apart. The request's own town comes first,
     * since the nearest help is almost always there; the rest follow by town, then by name.
     *
     * @return list<array{name: string, town: string|null}>
     */
    private function barangayChoices(SosRequest $sos): array
    {
        $own = mb_strtolower(trim((string) $sos->municipality));

        return Barangay::with('municipality:id,name')
            ->get(['id', 'name', 'municipality', 'municipality_id'])
            ->map(fn (Barangay $b) => [
                'name' => $b->name,
                // The old text column covers rows not yet linked to a municipality.
                'town' => $b->getRelation('municipality')?->name ?? $b->getAttribute('municipality'),
            ])
            ->sort(fn (array $a, array $b) => [mb_strtolower((string) $a['town']) !== $own, mb_strtolower((string) $a['town']), mb_strtolower($a['name'])]
                <=> [mb_strtolower((string) $b['town']) !== $own, mb_strtolower((string) $b['town']), mb_strtolower($b['name'])])
            ->values()
            ->all();
    }

    /**
     * Shows the photo or video attached to a request. Private: it is never reachable by a public
     * URL. Files from before the move to the private disk are still found on the media disk.
     */
    public function media(SosRequest $sos, string $kind)
    {
        $path = $sos->{"{$kind}_path"};
        abort_unless($path, 404);

        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];

        foreach (array_unique([SosRequest::mediaDisk(), Media::disk()]) as $name) {
            $disk = Storage::disk($name);
            if (! $disk->exists($path)) {
                continue;
            }

            // A file on this server is sent with byte-range support, so the video can be seeked.
            return $disk->getAdapter() instanceof LocalFilesystemAdapter
                ? response()->file($disk->path($path), $headers)
                : $disk->response($path, null, $headers);
        }

        abort(404);
    }

    /** Deletes the request with its photo and video (e.g. a test or a duplicate). */
    public function destroy(SosRequest $sos)
    {
        $who = $sos->user?->name ?? 'a resident';
        $sos->delete();
        $sos->deleteMediaFiles();

        return redirect()->route('admin.operations.sos.index')
            ->with('success', "SOS request from {$who} deleted.");
    }
}
