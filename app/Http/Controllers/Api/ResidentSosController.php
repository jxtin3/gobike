<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SosRequest;
use App\Support\Barangays;
use App\Support\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * The resident's SOS button. The request is saved first (so the admin is alerted even on a weak
 * connection) and the photo or video is uploaded afterwards with media().
 */
class ResidentSosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = SosRequest::where('user_id', $request->user()->id)
            ->whereNull('resident_hidden_at') // cleared from the resident's history
            ->latest()
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'sos' => $requests->map(fn (SosRequest $sos) => $this->payload($sos))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'municipality' => ['required', 'string', 'max:100'],
            'barangay' => ['required', 'string', Rule::in(Barangays::ALL)],
            'landmark' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);

        $sos = SosRequest::create($data + [
            'user_id' => $request->user()->id,
            'status' => 'sent',
        ]);

        return response()->json([
            'message' => 'Emergency request sent.',
            'sos' => $this->payload($sos),
        ], 201);
    }

    /** Attach a photo and/or a short video to the resident's own request. */
    public function media(Request $request, SosRequest $sos): JsonResponse
    {
        abort_unless($sos->user_id === $request->user()->id, 403, 'You can only add media to your own request.');

        $request->validate([
            'image' => ['required_without:video', 'nullable', 'image', 'max:5120'],
            // Phones record mp4 (Android, most iPhones), mov or 3gp.
            'video' => ['required_without:image', 'nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/3gpp', 'max:51200'],
        ]);

        $disk = Storage::disk(Media::disk());
        foreach (['image', 'video'] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }
            $old = $sos->{"{$field}_path"};
            // A long random file name: these URLs are only shown to the resident and the admins.
            $sos->{"{$field}_path"} = $request->file($field)->store('sos', Media::disk());
            if ($old) {
                $disk->delete($old);
            }
        }
        $sos->save();

        return response()->json([
            'message' => 'Media uploaded.',
            'sos' => $this->payload($sos),
        ]);
    }

    /** The resident no longer needs help and closes their own request. */
    public function resolve(Request $request, SosRequest $sos): JsonResponse
    {
        abort_unless($sos->user_id === $request->user()->id, 403, 'You can only resolve your own request.');

        if (! $sos->canMoveTo('resolved')) {
            return response()->json(['message' => 'This request is already resolved.'], 422);
        }

        $sos->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by_resident' => true,
        ]);

        return response()->json([
            'message' => 'SOS request resolved. Glad you are okay.',
            'sos' => $this->payload($sos),
        ]);
    }

    private function payload(SosRequest $sos): array
    {
        return [
            'id' => $sos->id,
            'status' => $sos->status,
            'status_label' => $sos->status_label,
            'municipality' => $sos->municipality,
            'barangay' => $sos->barangay,
            'landmark' => $sos->landmark,
            'description' => $sos->description,
            'has_location' => $sos->latitude !== null,
            'image_url' => $sos->image_url,
            'video_url' => $sos->video_url,
            'created_at' => $sos->created_at?->toIso8601String(),
            'responded_at' => $sos->responded_at?->toIso8601String(),
            'resolved_at' => $sos->resolved_at?->toIso8601String(),
            'resolved_by_resident' => (bool) $sos->resolved_by_resident,
        ];
    }
}
