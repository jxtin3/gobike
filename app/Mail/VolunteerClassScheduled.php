<?php

namespace App\Mail;

use App\Models\VolunteerApplication;
use App\Models\VolunteerClass;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells a volunteer the date and venue of the training class they were put in. */
class VolunteerClassScheduled extends Mailable
{
    public function __construct(
        public VolunteerApplication $application,
        public VolunteerClass $class,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Go Bike volunteer training: '.$this->class->starts_on->format('l, F j'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.volunteer-class-scheduled');
    }
}
