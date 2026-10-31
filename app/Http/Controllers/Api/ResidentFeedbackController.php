<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Patient;
use App\Models\SosRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The rating pop-up in the resident app: after a GoBiker resolves their SOS or records their
 * check-up, the resident rates that GoBiker from 1 to 5 stars (or skips).
 */
class ResidentFeedbackController extends Controller
{
    /** Older events are not asked about any more. */
    private const ASK_DAYS = 3;

    /** The oldest event the resident has not rated or skipped yet, or null. */
    public function pending(Request $request): JsonResponse
    {
        $resident = $request->user();
        $since = now()->subDays(self::ASK_DAYS);

        $sos = SosRequest::with('resolvedBy:id,name')
            ->where('user_id', $resident->id)
            ->where('status', 'resolved')
            ->whereNotNull('resolved_by_gobiker_id')
            ->where('resolved_at', '>=', $since)
            ->whereNotIn('id', Feedback::where('kind', 'sos')->select('subject_id'))
            ->oldest('resolved_at')
            ->first();
        if ($sos) {
            return response()->json(['pending' => [
                'kind' => 'sos',
                'subject_id' => $sos->id,
                'title' => 'How was the response to your SOS?',
                'gobiker' => ['id' => $sos->resolvedBy?->id, 'name' => $sos->resolvedBy?->name],
                'at' => $sos->resolved_at?->toIso8601String(),
            ]]);
        }

        $checkup = Patient::with('recorder:id,name')
            ->where('resident_id', $resident->id)
            ->where('created_at', '>=', $since)
            ->whereNotIn('id', Feedback::where('kind', 'checkup')->select('subject_id'))
            ->oldest()
            ->first();
        if ($checkup) {
            return response()->json(['pending' => [
                'kind' => 'checkup',
                'subject_id' => $checkup->id,
                'title' => 'How was your check-up?',
                'gobiker' => ['id' => $checkup->recorder?->id, 'name' => $checkup->recorder?->name],
                'at' => $checkup->created_at?->toIso8601String(),
            ]]);
        }

        return response()->json(['pending' => null]);
    }

    /** Save the rating. No rating means the resident skipped: they are not asked again. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(Feedback::KINDS)],
            'subject_id' => ['required', 'integer'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);
        $resident = $request->user();

        $gobikerId = match ($data['kind']) {
            'sos' => SosRequest::where('user_id', $resident->id)
                ->where('status', 'resolved')
                ->whereNotNull('resolved_by_gobiker_id')
                ->findOrFail($data['subject_id'])
                ->resolved_by_gobiker_id,
            'checkup' => Patient::where('resident_id', $resident->id)
                ->findOrFail($data['subject_id'])
                ->user_id,
        };

        if (Feedback::where('kind', $data['kind'])->where('subject_id', $data['subject_id'])->exists()) {
            return response()->json(['message' => 'You already rated this.'], 422);
        }

        Feedback::create([
            'resident_id' => $resident->id,
            'gobiker_id' => $gobikerId,
            'kind' => $data['kind'],
            'subject_id' => $data['subject_id'],
            'rating' => $data['rating'] ?? null,
            'comment' => isset($data['rating']) ? ($data['comment'] ?? null) : null,
        ]);

        return response()->json([
            'message' => isset($data['rating']) ? 'Thank you for your feedback!' : 'Okay, maybe next time.',
        ], 201);
    }
}
