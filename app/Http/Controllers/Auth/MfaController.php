<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Mfa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-step login for admins: setting it up, and new backup codes. Admins cannot turn it off;
 * an admin who lost the phone and the backup codes runs `php artisan admin:mfa-reset`.
 */
class MfaController extends Controller
{
    public function setup(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasMfaEnabled()) {
            return redirect()->route('admin.profile.show')->with('status', 'Two-step login is already on.');
        }

        $secret = Mfa::startSetup($user);
        $qrCodeUrl = app(Google2FA::class)->getQRCodeInline(config('app.name'), $user->email, $secret, 200);

        return view('auth.mfa-setup', [
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => trim(chunk_split($secret, 4, ' ')),
            'required' => (bool) $request->session()->get('mfa_setup_required'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);

        /** @var User $user */
        $user = $request->user();

        if ($user->hasMfaEnabled()) {
            return redirect()->route('admin.profile.show');
        }

        $codes = Mfa::enable($user, $data['code']);
        if ($codes === null) {
            return back()->withErrors(['code' => 'That code is wrong. Try the newest code in your authenticator app.']);
        }

        $request->session()->forget('mfa_setup_required');
        $request->session()->put('mfa_verified', true);

        return $this->showCodes($request, $codes, 'Two-step login is on.');
    }

    /** New backup codes; the old ones stop working. Needs a current code. */
    public function regenerateBackupCodes(Request $request): RedirectResponse
    {
        $data = $request->validate(['mfa_code' => ['required', 'string', 'max:20']]);

        /** @var User $user */
        $user = $request->user();

        if (! Mfa::verify($user, $data['mfa_code'])) {
            return back()->withErrors(['mfa_code' => 'That code is wrong.']);
        }

        return $this->showCodes($request, Mfa::newBackupCodes($user), 'New backup codes made. The old ones no longer work.');
    }

    /** Shows the codes once: they are only in the flashed session data, never stored plain. */
    public function backupCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('mfa_new_codes');
        if (! $codes) {
            return redirect()->route('admin.profile.show');
        }

        return view('auth.mfa-backup-codes', [
            'backupCodes' => $codes,
            'title' => $request->session()->get('mfa_codes_title'),
            'continueUrl' => $request->session()->pull('url.intended', route('admin.dashboard')),
        ]);
    }

    private function showCodes(Request $request, array $codes, string $title): RedirectResponse
    {
        $request->session()->flash('mfa_new_codes', $codes);
        $request->session()->flash('mfa_codes_title', $title);

        return redirect()->route('admin.mfa.codes');
    }
}
