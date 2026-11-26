<?php

namespace App\Mail;

use App\Models\User;
use App\Support\PasswordResetCodes;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** The 6-digit code someone asked for to reset a forgotten password. */
class PasswordResetCode extends Mailable
{
    public function __construct(public User $user, public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Go Bike password reset code');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.password-reset-code', with: [
            'firstName' => strtok(trim($this->user->name), ' ') ?: $this->user->name,
            'minutes' => PasswordResetCodes::MINUTES,
        ]);
    }
}
