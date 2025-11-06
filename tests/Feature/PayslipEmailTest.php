<?php

declare(strict_types=1);

use App\Jobs\SendPayslipJob;
use App\Mail\PayslipSentMail;
use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    // Create admin role
    $adminRole = Role::create(['name' => 'admin']);

    // Create admin user with detail
    $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    UserDetail::create([
        'user_id' => $this->admin->id,
        'hourly_rate' => 150.00,
        'position' => 'Admin',
        'gender' => 'male',
        'civil_status' => 'single',
        'nationality' => 'Filipino',
        'hire_date' => now()->subYear(),
        'birthday' => now()->subYears(30),
        'pagibig' => '123456789',
        'sss' => '987654321',
        'tin' => '111222333',
        'philhealth' => '444555666',
        'street' => 'Test Street',
    ]);

    // Create employee
    $this->employee = User::factory()->create(['email' => 'employee@test.com']);
    UserDetail::create([
        'user_id' => $this->employee->id,
        'hourly_rate' => 100.00,
        'position' => 'Employee',
        'gender' => 'male',
        'civil_status' => 'single',
        'nationality' => 'Filipino',
        'hire_date' => now()->subYear(),
        'birthday' => now()->subYears(25),
        'pagibig' => '111111111',
        'sss' => '222222222',
        'tin' => '333333333',
        'philhealth' => '444444444',
        'street' => 'Employee Street',
    ]);

    // Create pay period
    $this->period = PayPeriod::create([
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->startOfMonth()->addDays(14),
        'pay_date' => now()->startOfMonth()->addDays(17),
        'label' => 'Test Period',
        'is_locked' => false,
    ]);

    // Create payslip
    $this->payslip = Payslip::create([
        'user_id' => $this->employee->id,
        'pay_period_id' => $this->period->id,
        'status' => 'draft',
        'sent_at' => null,
    ]);
});

test('sendPayslip dispatches SendPayslipJob', function () {
    Queue::fake();

    $this->actingAs($this->admin);

    Livewire::test('admin.pay-periods.payslip-form', ['payslip' => $this->payslip])
        ->call('sendPayslip')
        ->assertDispatched('toast', type: 'success', message: 'Payslip is being sent via email...');

    Queue::assertPushed(SendPayslipJob::class, function ($job) {
        return $job->payslipId === $this->payslip->id;
    });
});

test('SendPayslipJob sends email with PDF attachment', function () {
    Mail::fake();

    $job = new SendPayslipJob($this->payslip->id);
    $job->handle();

    Mail::assertQueued(PayslipSentMail::class, function ($mail) {
        return $mail->hasTo($this->employee->email)
            && $mail->payslip->id === $this->payslip->id
            && ! empty($mail->pdfContent);
    });
});

test('SendPayslipJob updates payslip status and sent_at', function () {
    Mail::fake();

    expect($this->payslip->status)->toBe('draft');
    expect($this->payslip->sent_at)->toBeNull();

    $job = new SendPayslipJob($this->payslip->id);
    $job->handle();

    $this->payslip->refresh();

    expect($this->payslip->status)->toBe('sent');
    expect($this->payslip->sent_at)->not->toBeNull();
});

test('PayslipSentMail has correct subject', function () {
    Mail::fake();

    $job = new SendPayslipJob($this->payslip->id);
    $job->handle();

    Mail::assertQueued(PayslipSentMail::class, function ($mail) {
        $expectedSubject = 'Your Payslip for '.$this->period->display_label;

        return $mail->envelope()->subject === $expectedSubject;
    });
});

test('PayslipSentMail includes PDF attachment', function () {
    Mail::fake();

    $job = new SendPayslipJob($this->payslip->id);
    $job->handle();

    Mail::assertQueued(PayslipSentMail::class, function ($mail) {
        $attachments = $mail->attachments();

        return count($attachments) === 1;
    });
});

test('SendPayslipJob loads required relationships', function () {
    Mail::fake();

    $job = new SendPayslipJob($this->payslip->id);
    $job->handle();

    Mail::assertQueued(PayslipSentMail::class, function ($mail) {
        return $mail->payslip->relationLoaded('user')
            && $mail->payslip->relationLoaded('payPeriod');
    });
});
