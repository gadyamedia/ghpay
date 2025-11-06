<?php

namespace App\Mail;

use App\Models\TestInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TestInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TestInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: 'Typing Test Invitation - '.$this->invitation->candidate->position_applied,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test-invitation',
            with: [
                'testUrl' => $this->generateTestUrl(),
                'candidate' => $this->invitation->candidate,
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    protected function generateTestUrl(): string
    {
        return URL::signedRoute(
            'test.take',
            ['token' => $this->invitation->token],
            $this->invitation->expires_at
        );
    }
}
