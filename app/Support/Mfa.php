<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-step login with an authenticator app (TOTP), for the admin website and the app.
 *
 * A login code is the 6 digits the authenticator app shows, or one of the 10 backup codes
 * (xxxx-xxxx) given when it was turned on. Each backup code works once.
 */
final class Mfa
{
    public const BACKUP_CODES = 10;

    /** No 0/o, 1/l/i: a backup code is read off paper and typed in. */
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /**
     * The secret to put in the authenticator app. Visiting setup again (a reload, a second try)
     * keeps the same one, so an app that already scanned it keeps working.
     */
    public static function startSetup(User $user): string
    {
        if ($user->hasMfaEnabled()) {
            return $user->mfa_secret;
        }

        if (! $user->mfa_secret) {
            $user->mfa_secret = app(Google2FA::class)->generateSecretKey();
            $user->save();
        }

        return $user->mfa_secret;
    }

    /** otpauth:// link for the QR code, or for "Open in authenticator app" on the phone. */
    public static function otpauthUrl(User $user): string
    {
        return app(Google2FA::class)->getQRCodeUrl(config('app.name'), $user->email, $user->mfa_secret);
    }

    /** True for the current authenticator code, or an unused backup code (which is then used up). */
    public static function verify(User $user, ?string $code): bool
    {
        if (! $user->hasMfaEnabled() || ! $user->mfa_secret) {
            return false;
        }

        return self::checkTotp($user, $code) || self::useBackupCode($user, $code);
    }

    /**
     * Turns MFA on when the code from the authenticator app is right. Returns the plain backup
     * codes, shown to the user once, or null for a wrong code.
     *
     * @return list<string>|null
     */
    public static function enable(User $user, ?string $code): ?array
    {
        if ($user->hasMfaEnabled() || ! $user->mfa_secret || ! self::checkTotp($user, $code)) {
            return null;
        }

        $codes = self::makeBackupCodes();
        $user->forceFill([
            'mfa_enabled_at' => now(),
            'mfa_backup_codes' => array_map(fn ($c) => Hash::make(self::normalize($c)), $codes),
        ]);
        // A browser that stayed signed in with "Remember me" must now sign in again with a code.
        $user->setRememberToken(Str::random(60));
        $user->save();

        return $codes;
    }

    /**
     * New backup codes; the old ones stop working.
     *
     * @return list<string>
     */
    public static function newBackupCodes(User $user): array
    {
        $codes = self::makeBackupCodes();
        $user->forceFill([
            'mfa_backup_codes' => array_map(fn ($c) => Hash::make(self::normalize($c)), $codes),
        ])->save();

        return $codes;
    }

    public static function disable(User $user): void
    {
        $user->forceFill([
            'mfa_secret' => null,
            'mfa_enabled_at' => null,
            'mfa_backup_codes' => null,
        ])->save();
    }

    public static function backupCodesLeft(User $user): int
    {
        return count($user->mfa_backup_codes ?? []);
    }

    private static function checkTotp(User $user, ?string $code): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);

        return preg_match('/^\d{6}$/', $code) === 1
            && (bool) app(Google2FA::class)->verifyKey($user->mfa_secret, $code);
    }

    private static function useBackupCode(User $user, ?string $code): bool
    {
        $code = self::normalize((string) $code);
        if (strlen($code) !== 8) {
            return false;
        }

        $hashes = $user->mfa_backup_codes ?? [];
        foreach ($hashes as $i => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashes[$i]);
                $user->forceFill(['mfa_backup_codes' => array_values($hashes)])->save();

                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private static function makeBackupCodes(): array
    {
        $max = strlen(self::ALPHABET) - 1;
        $codes = [];
        for ($i = 0; $i < self::BACKUP_CODES; $i++) {
            $chars = '';
            for ($j = 0; $j < 8; $j++) {
                $chars .= self::ALPHABET[random_int(0, $max)];
            }
            $codes[] = substr($chars, 0, 4).'-'.substr($chars, 4);
        }

        return $codes;
    }

    private static function normalize(string $code): string
    {
        return Str::lower(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }
}
