<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells a GoBiker their sign-up was approved and they can now sign in to the app. */
class GoBikerApproved extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Go Bike GoBiker account is approved');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.gobiker-approved', with: [
            'firstName' => strtok(trim($this->user->name), ' ') ?: $this->user->name,
        ]);
    }
}
