<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You\'ve been invited to :app', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user_invitation',
            with: [
                'user' => $this->user,
                'plainPassword' => $this->plainPassword,
                'appName' => config('app.name'),
            ],
        );
    }
}
