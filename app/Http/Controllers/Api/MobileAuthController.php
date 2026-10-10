<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower(trim($credentials['email'])))->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        if (! $this->isActive($user)) {
            return $this->inactiveResponse();
        }

        return $this->tokenResponse($user, 'Login successful.');
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'string', 'max:30'],
            'barangay' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['Resident', 'GoBiker', 'User'])],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        // GoBikers can see/track people, so they must be approved by an admin first.
        $isGoBiker = $data['role'] === 'GoBiker';

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'mobile' => trim($data['mobile']),
            'barangay' => $data['barangay'],
            'password' => $data['password'], // hashed automatically by the model
            'role' => $isGoBiker ? 'GoBiker' : 'User',
            'status' => $isGoBiker ? 'Inactive' : 'Active',
            'is_admin' => false,
        ]);

        return response()->json([
            'message' => $isGoBiker
                ? 'Account created. An admin must approve your GoBiker account before you can log in.'
                : 'Account created successfully.',
            'pending_approval' => $isGoBiker,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function google(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $clientId = config('services.google.client_id');
        if (blank($clientId)) {
            return response()->json(['message' => 'Google sign-in is not configured on the server.'], 500);
        }

        try {
            $client = new GoogleClient(['client_id' => $clientId]);
            $payload = $client->verifyIdToken($request->input('id_token'));
        } catch (\Throwable $e) {
            Log::warning('Google token verification failed: '.$e->getMessage());
            $payload = false;
        }

        if (
            ! $payload ||
            empty($payload['email']) ||
            empty($payload['sub']) ||
            ! filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ) {
            return response()->json(['message' => 'Google sign-in could not be verified. Please try again.'], 401);
        }

        $googleId = $payload['sub'];
        $email = strtolower($payload['email']);

        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            if ($user->google_id && $user->google_id !== $googleId) {
                return response()->json(['message' => 'This email is linked to a different Google account.'], 403);
            }

            $user->google_id = $googleId;
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->save();
        } else {
            $user = User::create([
                'name' => $payload['name'] ?? Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'password' => Str::random(40), // nobody knows it; Google users sign in with Google
                'role' => 'User',
                'status' => 'Active',
                'is_admin' => false,
            ]);
            $user->email_verified_at = now();
            $user->save();
        }

        if (! $this->isActive($user)) {
            return $this->inactiveResponse();
        }

        return $this->tokenResponse($user, 'Login successful.');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    // ---------- helpers ----------

    private function isActive(User $user): bool
    {
        return ($user->status ?? 'Active') === 'Active';
    }

    private function inactiveResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Your account is not active yet. Please wait for admin approval or contact your admin.',
        ], 403);
    }

    private function tokenResponse(User $user, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'token' => $user->createToken('go-biker-mobile')->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'barangay' => $user->barangay,
            'role' => $user->role,
        ];
    }
}