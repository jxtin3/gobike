<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Mfa;
use Illuminate\Console\Command;

/**
 * For an admin who lost their phone and their backup codes. The admin signs in with the
 * password again and is sent straight to setting up two-step login again.
 */
class ResetAdminMfa extends Command
{
    protected $signature = 'admin:mfa-reset {email}';

    protected $description = 'Turn off two-step login for an admin who lost their phone and backup codes.';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No account uses {$email}.");

            return self::FAILURE;
        }

        if (! $user->hasMfaEnabled()) {
            $this->info("{$user->name} does not have two-step login on. Nothing to do.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Turn off two-step login for {$user->name} ({$email})? Do this only if you are sure it is really them.")) {
            $this->line('Nothing changed.');

            return self::SUCCESS;
        }

        Mfa::disable($user);
        $user->tokens()->delete();

        $this->info("Two-step login is off for {$user->name}. They set it up again at their next sign-in.");

        return self::SUCCESS;
    }
}
