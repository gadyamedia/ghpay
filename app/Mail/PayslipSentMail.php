<?php

namespace App\Mail;

use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Payslip $payslip,
        public string $pdfContent,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $period = $this->payslip->payPeriod;

        return new Envelope(
            subject: 'Your Payslip for '.$period->display_label,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payslip-sent',
            with: [
                'payslip' => $this->payslip,
                'period' => $this->payslip->payPeriod,
                'user' => $this->payslip->user,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $fileName = 'Payslip_'.$this->payslip->user->name.'_'.$this->payslip->payPeriod->label.'.pdf';
        $fileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $fileName);

        return [
            Attachment::fromData(fn () => $this->pdfContent, $fileName)
                ->withMime('application/pdf'),
        ];
    }
}
