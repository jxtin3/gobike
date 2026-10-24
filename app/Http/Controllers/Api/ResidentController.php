<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use App\Support\Barangays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The resident side of the mobile app. */
class ResidentController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Not unique like a GoBiker's number: family members often share one phone.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'barangay' => ['required', 'string', Rule::in(Barangays::ALL)],
        ]);

        $user->update([
            'name' => trim($data['name']),
            'mobile' => $data['mobile'],
            'barangay' => $data['barangay'],
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => MobileAuthController::userPayload($user->fresh()),
        ]);
    }

    /** The resident's own check-ups, newest first. Never anyone else's. */
    public function visits(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $visits = $user->checkups()
            ->with('recorder:id,name')
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'visits' => $visits->map(fn (Patient $p) => [
                'id' => $p->id,
                'recorded_at' => $p->recorded_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
                'barangay' => $p->barangay,
                'gobiker_name' => $p->recorder?->name,
                'name' => $p->name,
                'address' => $p->address,
                'contact' => $p->contact,
                'age' => $p->age,
                'sys' => $p->sys,
                'dia' => $p->dia,
                'pulse' => $p->pulse,
                'resp' => $p->resp,
                'temp' => $p->temp,
                'blood_sugar' => $p->blood_sugar,
                'remarks' => $p->remarks,
            ])->values(),
        ]);
    }
}
