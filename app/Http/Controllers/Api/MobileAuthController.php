<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Push\PushNotifier;
use App\Support\Age;
use App\Support\Barangays;
use App\Support\Media;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            'barangay' => ['required', 'string', Rule::in(Barangays::ALL)],
            'role' => ['required', Rule::in(['Resident', 'GoBiker', 'User'])],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            // Old enough to use the app (see App\Support\Age). The sign-up screen will not submit
            // without one, and refuses anyone under the minimum before it gets here.
            'birthdate' => Age::rules($request->input('role')),
            // A GoBiker is sent to people's homes, and a resident is the person they are sent to,
            // so each must be able to see who the other is. The sign-up screen will not submit
            // without a photo.
            //
            // TEMPORARY: the server still does not require the photo or the birthdate. Released
            // apps (up to 1.0.20) send neither for a resident, and older ones send no birthdate
            // for anyone; requiring them here would stop every sign-up from the apps people have.
            // Once the testers are on the new version, make 'photo' and 'birthdate' required for
            // everyone, and turn the "still registers from an older app" tests into "will not
            // register without ..." ones.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'photo.uploaded' => 'The photo could not be uploaded. Please try a smaller one.',
            ...Age::messages($request->input('role')),
        ]);

        // GoBikers can see/track people, so they must be approved by an admin first.
        $isGoBiker = $data['role'] === 'GoBiker';

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'mobile' => trim($data['mobile']),
            'barangay' => $data['barangay'],
            'birthdate' => $data['birthdate'] ?? null,
            'photo_path' => $request->hasFile('photo')
                ? $request->file('photo')->store('profile-photos', Media::disk())
                : null,
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
            $client = app(GoogleClient::class, ['config' => ['client_id' => $clientId]]);
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

            // Anyone can register an email with a password without proving they own it.
            // Google has now proven who owns it, so cut off whoever set that password.
            if (! $user->google_id && ! $user->email_verified_at && ! $user->is_admin) {
                $user->password = Str::random(40);
                $user->has_password = false;
                $user->tokens()->delete();
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
                'has_password' => false,
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

    /**
     * Any signed-in mobile user. Other phones are signed out; this one stays signed in.
     * A Google sign-in account has no password of its own yet, so it sets one without a current one.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Unknown (not loaded) counts as having one: only Google accounts are marked false.
        $data = $request->validate(($user->has_password ?? true) ? [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed', 'different:current_password'],
        ] : [
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $user->password = $data['password']; // hashed automatically by the model
        $user->has_password = true;
        $user->save();
        $user->logoutOtherDevices();

        return response()->json([
            'message' => 'Password changed successfully.',
            'user' => self::userPayload($user),
        ]);
    }

    /** The phone's Firebase token, so this user gets push notifications on it. */
    public function storeDeviceToken(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:512']]);

        // A phone can change hands (logout, another account logs in): the token moves with it.
        // Touched on every registration, so a freshly registered phone is not forgotten too early.
        DeviceToken::updateOrCreate(['token' => $data['token']], ['user_id' => $request->user()->id])->touch();

        return response()->json(['message' => 'Device registered.']);
    }

    /** Called on logout so the phone stops getting this user's notifications. */
    public function destroyDeviceToken(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:512']]);
        DeviceToken::where('user_id', $request->user()->id)->where('token', $data['token'])->delete();

        return response()->json(['message' => 'Device removed.']);
    }

    /** The resident's notification switches from the app. */
    public function updateNotificationPrefs(Request $request): JsonResponse
    {
        $rules = [];
        foreach (array_keys(PushNotifier::PREFS) as $key) {
            $rules[$key] = ['sometimes', 'boolean'];
        }
        $data = $request->validate($rules);

        /** @var User $user */
        $user = $request->user();
        $user->notification_prefs = array_merge($user->notificationPrefs(), array_map('boolval', $data));
        $user->save();

        return response()->json(['notification_prefs' => $user->notificationPrefs()]);
    }

    /**
     * Sets or replaces the signed-in user's profile photo.
     *
     * GoBikers who registered before a photo was required use this to add one; anyone can use it
     * to change theirs. The file it replaces is deleted, so nothing is left behind.
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'photo.uploaded' => 'The photo could not be uploaded. Please try a smaller one.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $old = $user->photo_path;
        $user->photo_path = $request->file('photo')->store('profile-photos', Media::disk());
        $user->save();
        if ($old) {
            Storage::disk(Media::disk())->delete($old);
        }

        return response()->json([
            'message' => 'Profile photo updated.',
            'user' => $this->userPayload($user),
        ]);
    }

    /** Takes the profile photo off the account, and the file off the disk. */
    public function removePhoto(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $old = $user->photo_path;
        $user->photo_path = null;
        $user->save();
        if ($old) {
            Storage::disk(Media::disk())->delete($old);
        }

        return response()->json([
            'message' => 'Profile photo removed.',
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * "Delete account" in the app (Privacy Policy, Section 14). Removes the account and what
     * belongs to it: profile photo, location, device tokens, rondas, SOS requests with their
     * photos and videos, visit requests, ratings and messages (User::booted and the foreign
     * keys). Check-ups recorded about a resident are kept, no longer linked to the account.
     *
     * Someone who recorded check-ups is refused: those are health records of other people, and
     * deleting the account would delete them too. The admin closes such accounts by hand.
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->is_admin || $user->role === 'Admin') {
            return response()->json(['message' => 'Admin accounts cannot be deleted from the app.'], 403);
        }

        if ($user->patients()->exists()) {
            return response()->json([
                'message' => 'You recorded check-ups, and those health records must be kept. Email gobike@pydn.org and we will close your account.',
            ], 409);
        }

        // Unknown (not loaded) counts as having one: only Google accounts are marked false.
        $request->validate(($user->has_password ?? true)
            ? ['password' => ['required', 'string', 'current_password']]
            : ['confirm' => ['required', 'string', 'in:DELETE']], [
                'password.current_password' => 'The password is incorrect.',
                'confirm.in' => 'Type DELETE to confirm.',
            ]);

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Your account has been deleted.']);
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

    /** The user as the mobile app knows it (login, /mobile/me, profile updates). */
    public static function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'barangay' => $user->barangay,
            'birthdate' => $user->birthdate?->toDateString(),
            'age' => $user->age,
            'photo_url' => Media::url($user->photo_path),
            'role' => $user->role,
            // Ronda days and hours set by the admin (GoBikers only; null when not set).
            // False for Google sign-in accounts: the app offers "Set a password" instead of "Change".
            'has_password' => (bool) ($user->has_password ?? true),
            'notification_prefs' => $user->notificationPrefs(),
        ];
    }
}
