<?php

namespace App\Jobs;

use App\Mail\TestInvitationMail;
use App\Models\TestInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendTestInvitationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TestInvitation $invitation
    ) {}

    public function handle(): void
    {
        Mail::to($this->invitation->candidate->email)
            ->send(new TestInvitationMail($this->invitation));
    }
}
