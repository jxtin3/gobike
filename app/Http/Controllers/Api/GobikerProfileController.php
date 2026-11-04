<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Age;
use App\Support\Barangays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GobikerProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'mobile' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                Rule::unique('users', 'mobile')->ignore($user->id),
            ],
            'barangay' => ['required', 'string', Rule::in(Barangays::ALL)],
            // Optional: an account made before the birthdate was asked can add it here.
            'birthdate' => Age::rules($user->role),
            'current_password' => ['required_with:password', 'string', 'current_password'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'password_confirmation' => ['required_with:password', 'string', 'same:password'],
        ], Age::messages($user->role));

        $user->mobile = $data['mobile'];
        $user->barangay = $data['barangay'];
        if (! empty($data['birthdate'])) {
            $user->birthdate = $data['birthdate'];
        }

        if (array_key_exists('password', $data)) {
            $user->password = Hash::make($data['password']);
            $user->logoutOtherDevices();
        }

        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => MobileAuthController::userPayload($user),
        ]);
    }
}
