<?php

namespace App\Jobs;

use App\Mail\PayslipSentMail;
use App\Models\PayPeriodLog;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPayslipJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $payslipId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payslip = Payslip::with(['user', 'payPeriod', 'earnings', 'deductions'])
            ->findOrFail($this->payslipId);

        // Generate PDF content
        $pdf = Pdf::loadView('pdf.payslip', ['payslip' => $payslip]);
        $pdfContent = $pdf->output();

        // Send email with PDF attachment
        Mail::to($payslip->user->email)
            ->send(new PayslipSentMail($payslip, $pdfContent));

        // Update payslip status and sent timestamp
        $payslip->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Log the send action
        PayPeriodLog::logAction(
            $payslip->payPeriod,
            'payslip_sent',
            "Payslip sent to {$payslip->user->name} ({$payslip->user->email})",
            null
        );
    }
}
