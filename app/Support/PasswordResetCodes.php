<?php

namespace App\Support;

use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * "Forgot password" by a 6-digit code sent by email, for the admin website and the app.
 *
 * A code lasts 15 minutes and dies after 5 wrong tries. A new one can be sent after 60 seconds,
 * and it replaces the old one. Whether the email has an account is never revealed.
 */
final class PasswordResetCodes
{
    public const MINUTES = 15;

    public const TRIES = 5;

    public const RESEND_SECONDS = 60;

    public const SENT_MESSAGE = 'If that email has an account, we sent a code.';

    public const WRONG_CODE = 'That code is wrong or has expired.';

    public static function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    /** Mails a new code when the email has an account. Says nothing either way. */
    public static function send(string $email): void
    {
        $email = self::normalize($email);
        $row = DB::table('password_reset_codes')->where('email', $email)->first();

        if ($row && Carbon::parse($row->created_at)->gt(now()->subSeconds(self::RESEND_SECONDS))) {
            return;
        }

        $user = User::where('email', $email)->first();
        if (! $user || ! $user->canReceiveEmail()) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_codes')->updateOrInsert(['email' => $email], [
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::MINUTES),
            'created_at' => now(),
        ]);

        try {
            Mail::to($user->email, $user->name)->send(new PasswordResetCode($user, $code));
        } catch (Throwable $e) {
            // The answer must not show whether the email has an account, so the error is only logged.
            report($e);
        }
    }

    /** True when the code is right. A wrong one counts as a try. */
    public static function check(string $email, string $code): bool
    {
        $email = self::normalize($email);
        $row = DB::table('password_reset_codes')->where('email', $email)->first();

        if (! $row || Carbon::parse($row->expires_at)->isPast() || $row->attempts >= self::TRIES) {
            return false;
        }

        if (Hash::check(trim($code), $row->code_hash)) {
            return true;
        }

        DB::table('password_reset_codes')->where('email', $email)->increment('attempts');

        return false;
    }

    /**
     * Sets the new password and uses up the code. Every phone and every other browser is signed
     * out. Two-step login, when it is on, is still asked at the next sign-in.
     */
    public static function reset(string $email, string $password): void
    {
        $email = self::normalize($email);
        $user = User::where('email', $email)->firstOrFail();

        $user->password = $password; // hashed automatically by the model
        $user->has_password = true;
        $user->setRememberToken(Str::random(60));
        $user->save();
        $user->tokens()->delete();

        DB::table('password_reset_codes')->where('email', $email)->delete();
    }
}
