<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {--name=} {--email=}';

    protected $description = 'Create an admin account. The password is typed at the prompt and never stored in code.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Full name');
        $email = strtolower(trim($this->option('email') ?: $this->ask('Email address')));
        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // forceFill: is_admin is deliberately not mass-assignable.
        (new User)->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password, // hashed automatically by the model
            'is_admin' => true,
            'role' => 'Admin',
            'status' => 'Active',
            'email_verified_at' => now(),
        ])->save();

        $this->info("Admin account created for {$email}.");

        return self::SUCCESS;
    }
}