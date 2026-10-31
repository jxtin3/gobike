<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SosRequest;
use App\Services\Push\PushNotifier;
use App\Support\LiveMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The GoBiker's live map and the SOS requests the admin sent them to.
 *
 * They see the other GoBikers of their own barangay, and the exact location of the SOS requests
 * the admin assigned to them. Residents are never plotted: a GoBiker picks their own barangay,
 * so plotting a barangay's residents would let anyone watch residents anywhere in the town.
 */
class GobikerMapController extends Controller
{
    /** A resolved SOS stays on the map (green) this long. */
    private const RESOLVED_MINUTES = 30;

    public function index(Request $request): JsonResponse
    {
        $gobiker = $request->user();
        $barangay = LiveMap::gobikerBarangay($gobiker);

        $sos = $this->assigned($request)
            ->where(fn ($q) => $q->where('status', 'responding')
                ->orWhere(fn ($q) => $q->where('status', 'resolved')->where('resolved_at', '>=', now()->subMinutes(self::RESOLVED_MINUTES))))
            ->get();

        return response()->json([
            'barangay' => $barangay,
            'gobikers' => LiveMap::onlineGobikers($barangay, $gobiker->id),
            'sos' => $sos->map(fn (SosRequest $s) => $this->payload($s))->values(),
        ]);
    }

    /** Open SOS requests assigned to this GoBiker (the dashboard card). */
    public function sos(Request $request): JsonResponse
    {
        $sos = $this->assigned($request)->where('status', 'responding')->get();

        return response()->json(['sos' => $sos->map(fn (SosRequest $s) => $this->payload($s))->values()]);
    }

    /** The GoBiker helped the resident: close the request. The resident is then asked to rate them. */
    public function resolve(Request $request, SosRequest $sos): JsonResponse
    {
        abort_unless($sos->assigned_gobiker_id === $request->user()->id, 403, 'This SOS request is not assigned to you.');

        if (! $sos->canMoveTo('resolved')) {
            return response()->json(['message' => "This request is already marked \"{$sos->status_label}\"."], 422);
        }

        $sos->update([
            'status' => 'resolved',
            'responded_at' => $sos->responded_at ?? now(),
            'resolved_at' => now(),
            'resolved_by_gobiker_id' => $request->user()->id,
        ]);
        app(PushNotifier::class)->sosStatusChanged($sos->load('user', 'resolvedBy'));

        return response()->json([
            'message' => 'SOS request resolved.',
            'sos' => $this->payload($sos),
        ]);
    }

    /**
     * The SOS requests this GoBiker may see: the ones the admin assigned to them, and no others.
     *
     * Deliberately not filtered by barangay. The admin can send the nearest GoBiker, who may
     * live in the next barangay, and that GoBiker must still see the request they were sent to.
     */
    private function assigned(Request $request): Builder
    {
        return SosRequest::with('user:id,name,mobile')
            ->where('assigned_gobiker_id', $request->user()->id)
            ->latest();
    }

    private function payload(SosRequest $sos): array
    {
        return [
            'id' => $sos->id,
            'status' => $sos->status,
            'resident_name' => $sos->user?->name,
            'mobile' => $sos->user?->mobile,
            'barangay' => $sos->barangay,
            'address' => $sos->address,
            'landmark' => $sos->landmark,
            'description' => $sos->description,
            'latitude' => $sos->latitude,
            'longitude' => $sos->longitude,
            'created_at' => $sos->created_at?->toIso8601String(),
            'responded_at' => $sos->responded_at?->toIso8601String(),
            'resolved_at' => $sos->resolved_at?->toIso8601String(),
        ];
    }
}
