<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\SosRequest;
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

    /**
     * "Clear history": hides the resident's finished SOS requests and closed home visits from
     * their own history. Open ones stay (they still matter), check-ups stay (medical record),
     * and the admin keeps every record.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $sos = SosRequest::where('user_id', $userId)
            ->where('status', 'resolved')
            ->whereNull('resident_hidden_at')
            ->update(['resident_hidden_at' => now()]);

        $visits = Appointment::where('resident_id', $userId)
            ->whereNotIn('status', Appointment::OPEN)
            ->whereNull('resident_hidden_at')
            ->update(['resident_hidden_at' => now()]);

        return response()->json([
            'message' => 'History cleared.',
            'cleared' => ['sos' => $sos, 'appointments' => $visits],
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
