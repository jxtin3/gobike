<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SosRequest;
use App\Models\SosResponder;
use App\Services\Push\PushNotifier;
use App\Support\GobikerEmergency;
use App\Support\LiveMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The GoBiker's live map and the SOS requests the admin sent them to.
 *
 * They see every other GoBiker of their town whose app is open, and the exact location of the
 * SOS requests the admin assigned to them. Residents are never plotted: a GoBiker picks their own
 * barangay, so plotting residents would let anyone watch residents anywhere in the town.
 */
class GobikerMapController extends Controller
{
    /** A resolved SOS stays on the map (green) this long, so the GoBiker sees it was closed. */
    private const RESOLVED_MINUTES = 2;

    public function index(Request $request): JsonResponse
    {
        $gobiker = $request->user();
        $barangay = LiveMap::gobikerBarangay($gobiker);

        // A cancelled SOS is not drawn: nobody helped, so a green "resolved" pin would mislead.
        $sos = $this->assigned($request)
            ->where(fn ($q) => $q->where('status', 'responding')
                ->orWhere(fn ($q) => $q->where('status', 'resolved')->where('resolved_at', '>=', now()->subMinutes(self::RESOLVED_MINUTES))))
            ->get();

        return response()->json([
            'barangay' => $barangay,
            'town' => LiveMap::townOf($barangay),
            'gobikers' => LiveMap::onlineGobikers($barangay, $gobiker->id),
            'sos' => $sos->map(fn (SosRequest $s) => $this->payload($s, $gobiker->id))->values(),
        ]);
    }

    /** Open SOS requests assigned to this GoBiker (the dashboard card). */
    public function sos(Request $request): JsonResponse
    {
        $sos = $this->assigned($request)->where('status', 'responding')->get();

        return response()->json(['sos' => $sos->map(fn (SosRequest $s) => $this->payload($s, $request->user()->id))->values()]);
    }

    /**
     * The GoBiker helped the resident: close the request, for every GoBiker sent to it. The
     * resident is then asked to rate this one; the others are told they can stand down.
     */
    public function resolve(Request $request, SosRequest $sos): JsonResponse
    {
        $me = $request->user();
        $row = $this->myRow($sos, $me->id);

        if (! $sos->canMoveTo('resolved') || ! $row->isResponding()) {
            return $this->notOpenForMe($sos, $row);
        }

        $sos->update([
            'status' => 'resolved',
            'responded_at' => $sos->responded_at ?? now(),
            'resolved_at' => now(),
            'resolved_by_gobiker_id' => $me->id,
        ]);
        GobikerEmergency::syncLocation($sos);
        $push = app(PushNotifier::class);
        $push->sosStatusChanged($sos->load('user', 'resolvedBy'));
        $push->sosResolvedForResponders($sos, $me);

        return response()->json([
            'message' => 'SOS request resolved.',
            'sos' => $this->payload($sos, $me->id),
        ]);
    }

    /**
     * The GoBiker cannot go to the request they were sent to. A reason is required: the admin
     * reads it on the map pin. Only this GoBiker comes off the request; while another GoBiker is
     * still on the way it stays "responding". When nobody is left, the request turns "declined"
     * (with who declined last, and why), which rings the admin's alarm so they send someone else.
     */
    public function decline(Request $request, SosRequest $sos): JsonResponse
    {
        $me = $request->user();
        $row = $this->myRow($sos, $me->id);

        if (! $sos->canMoveTo('declined') || ! $row->isResponding()) {
            return $this->notOpenForMe($sos, $row);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $row->update([
            'status' => SosResponder::DECLINED,
            'declined_at' => now(),
            'decline_reason' => $data['reason'],
        ]);

        $sos->responded_at ??= now();
        if ($sos->activeResponders()->exists()) {
            $sos->syncAssignedGobiker();
            $sos->save();
        } else {
            $sos->fill([
                'status' => 'declined',
                'declined_at' => now(),
                'declined_by_gobiker_id' => $me->id,
                'decline_reason' => $data['reason'],
            ])->save();
            GobikerEmergency::syncLocation($sos);
            // The resident is reassured on their phone; the admin learns of it through the
            // emergency alarm, which counts declined requests (see EmergencyAlertController).
            app(PushNotifier::class)->sosDeclined($sos->load('user', 'declinedBy'));
        }

        return response()->json([
            'message' => 'SOS request declined. The admin has been told.',
            'sos' => $this->payload($sos, $me->id),
        ]);
    }

    /** This GoBiker's row on the request; 403 when the admin never sent them. */
    private function myRow(SosRequest $sos, int $gobikerId): SosResponder
    {
        $row = $sos->responders()->where('gobiker_id', $gobikerId)->first();
        abort_unless($row, 403, 'This SOS request is not assigned to you.');

        return $row;
    }

    private function notOpenForMe(SosRequest $sos, SosResponder $row): JsonResponse
    {
        $message = in_array($sos->status, ['sent', 'responding', 'declined'], true) && ! $row->isResponding()
            ? 'You already declined this request.'
            : "This request is already marked \"{$sos->status_label}\".";

        return response()->json(['message' => $message], 422);
    }

    /**
     * The SOS requests this GoBiker may see: the ones the admin sent them to and that they have
     * not declined, and no others. Several GoBikers can be sent to one request.
     *
     * Deliberately not filtered by barangay. The admin can send the nearest GoBiker, who may
     * live in the next barangay, and that GoBiker must still see the request they were sent to.
     */
    private function assigned(Request $request): Builder
    {
        $me = $request->user()->id;

        return SosRequest::with([
            'user:id,name,mobile,role',
            'responders' => fn ($q) => $q->latest('assigned_at')->latest('id'),
            'responders.gobiker:id,name',
        ])
            ->whereHas('responders', fn ($q) => $q->where('gobiker_id', $me)->where('status', SosResponder::RESPONDING))
            ->latest();
    }

    private function payload(SosRequest $sos, int $me): array
    {
        // Loaded by assigned(); after resolve/decline it is read fresh.
        $responders = $sos->relationLoaded('responders')
            ? $sos->responders
            : $sos->responders()->with('gobiker:id,name')->get();
        $mine = $responders->firstWhere('gobiker_id', $me);

        return [
            'id' => $sos->id,
            // Apps from before "cancelled" existed would read an unknown status as still open.
            'status' => $sos->api_status,
            'cancelled' => $sos->isCancelled(),
            'status_label' => $sos->status_label,
            'resident_name' => $sos->user?->name,
            // Another GoBiker's own emergency rather than a resident's request.
            'from_gobiker' => $sos->fromGobiker(),
            'mobile' => $sos->user?->mobile,
            'barangay' => $sos->barangay,
            'address' => $sos->address,
            'landmark' => $sos->landmark,
            'description' => $sos->description,
            'latitude' => $sos->latitude,
            'longitude' => $sos->longitude,
            // How far out the pin may be, so the GoBiker knows when to trust the address instead.
            'accuracy' => $sos->location_accuracy,
            'position_at' => $sos->location_recorded_at?->toIso8601String(),
            'created_at' => $sos->created_at?->toIso8601String(),
            'responded_at' => $sos->responded_at?->toIso8601String(),
            'resolved_at' => $sos->resolved_at?->toIso8601String(),
            // When the admin (last) sent this GoBiker: the app alerts once per value, so being
            // sent again after declining rings again.
            'assigned_at' => $mine?->assigned_at?->toIso8601String(),
            // The other GoBikers also on the way.
            'other_responders' => $responders
                ->filter(fn (SosResponder $r) => $r->isResponding() && $r->gobiker_id !== $me)
                ->map(fn (SosResponder $r) => $r->gobiker?->name)
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
