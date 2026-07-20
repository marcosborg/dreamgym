<?php

namespace App\Mail;

use App\Models\PersonalTrainerSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PersonalTrainerSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PersonalTrainerSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nova candidatura de Personal Trainer');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.personal-trainer-submitted');
    }
}
