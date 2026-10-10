<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GobikerProfileController extends Controller
{
    private const BARANGAYS = [
        'Angarian',
        'Asinan',
        'Bañaga',
        'Bacabac',
        'Bolaoen',
        'Buenlag',
        'Cabayaoasan',
        'Cayanga',
        'Gueset',
        'Hacienda',
        'Laguit Centro',
        'Laguit Padilla',
        'Magtaking',
        'Pangascasan',
        'Pantal',
        'Poblacion',
        'Polong',
        'Portic',
        'Salasa',
        'Salomague Norte',
        'Salomague Sur',
        'Samat',
        'San Francisco',
        'Umanday',
    ];

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
            'barangay' => ['required', 'string', Rule::in(self::BARANGAYS)],
            'current_password' => ['required_with:password', 'string', 'current_password'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'password_confirmation' => ['required_with:password', 'string', 'same:password'],
        ]);

        $user->mobile = $data['mobile'];
        $user->barangay = $data['barangay'];

        if (array_key_exists('password', $data)) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'barangay' => $user->barangay,
                'role' => $user->role,
            ],
        ]);
    }
}
