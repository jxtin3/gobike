<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class MfaController extends Controller
{
    /**
     * Show the MFA setup page (QR code + secret).
     */
    public function setup(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->hasMfaEnabled()) {
            return redirect()->route('admin.profile.show')
                ->with('error', 'MFA is already enabled. Disable it first to set up again.');
        }

        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        // Store the secret temporarily (confirmed on verify)
        $user->mfa_secret = $secret;
        $user->save();

        $qrCodeUrl = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret,
            200,
            200,
        );

        return view('auth.mfa-setup', compact('qrCodeUrl', 'secret'));
    }

    /**
     * Verify a TOTP code and enable MFA.
     */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->mfa_secret) {
            return back()->withErrors(['code' => 'No MFA setup in progress. Please start setup again.']);
        }

        $google2fa = app(Google2FA::class);
        $valid = $google2fa->verifyKey($user->mfa_secret, $data['code']);

        if (! $valid) {
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        // Generate backup codes
        $backupCodes = collect(range(1, 10))->map(fn () => Str::random(8))->all();
        $hashedCodes = array_map(fn ($code) => Hash::make($code), $backupCodes);

        $user->forceFill([
            'mfa_enabled_at' => now(),
            'mfa_backup_codes' => $hashedCodes,
        ])->save();

        return view('auth.mfa-backup-codes', compact('backupCodes'));
    }

    /**
     * Disable MFA for the user.
     */
    public function disable(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->hasMfaEnabled()) {
            return back()->withErrors(['code' => 'MFA is not enabled.']);
        }

        $google2fa = app(Google2FA::class);
        $valid = $google2fa->verifyKey($user->mfa_secret, $data['code']);

        if (! $valid) {
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        $user->forceFill([
            'mfa_secret' => null,
            'mfa_enabled_at' => null,
            'mfa_backup_codes' => null,
        ])->save();

        return redirect()->route('admin.profile.show')
            ->with('success', 'MFA disabled.');
    }
}