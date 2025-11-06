<?php

declare(strict_types=1);

use App\Jobs\SendPayslipJob;
use App\Models\PayPeriod;
use App\Models\PayPeriodLog;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    // Create admin role
    $adminRole = Role::create(['name' => 'admin']);

    // Create admin user
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

    $this->period = PayPeriod::create([
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->startOfMonth()->addDays(14),
        'pay_date' => now()->startOfMonth()->addDays(17),
        'label' => 'Test Period',
        'is_locked' => false,
    ]);

    $this->payslip = Payslip::create([
        'user_id' => $this->employee->id,
        'pay_period_id' => $this->period->id,
        'status' => 'draft',
    ]);
});

test('acknowledging payslip creates log entry', function () {
    $this->payslip->update(['status' => 'sent', 'sent_at' => now()]);
    $this->actingAs($this->admin);

    expect(PayPeriodLog::count())->toBe(0);

    Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('acknowledgePayslip');

    expect(PayPeriodLog::count())->toBe(1);

    $log = PayPeriodLog::first();
    expect($log->pay_period_id)->toBe($this->period->id);
    expect($log->user_id)->toBe($this->admin->id);
    expect($log->action)->toBe('payslip_acknowledged');
    expect($log->note)->toContain($this->employee->name);
});

test('sending payslip creates log entry', function () {
    Mail::fake();

    expect(PayPeriodLog::count())->toBe(0);

    $job = new SendPayslipJob($this->payslip->id);
    $job->handle();

    expect(PayPeriodLog::count())->toBe(1);

    $log = PayPeriodLog::first();
    expect($log->pay_period_id)->toBe($this->period->id);
    expect($log->action)->toBe('payslip_sent');
    expect($log->note)->toContain($this->employee->name);
    expect($log->note)->toContain($this->employee->email);
});

test('locking period creates log entry', function () {
    $this->actingAs($this->admin);

    expect(PayPeriodLog::count())->toBe(0);

    Livewire::test(\App\Livewire\Admin\PayPeriods\PayPeriodManager::class)
        ->call('toggleLock', $this->period->id);

    expect(PayPeriodLog::count())->toBe(1);

    $log = PayPeriodLog::first();
    expect($log->pay_period_id)->toBe($this->period->id);
    expect($log->user_id)->toBe($this->admin->id);
    expect($log->action)->toBe('locked');
    expect($log->note)->toContain('locked by');
    expect($log->note)->toContain($this->admin->name);
});

test('unlocking period creates log entry', function () {
    $this->period->update(['is_locked' => true]);
    $this->actingAs($this->admin);

    expect(PayPeriodLog::count())->toBe(0);

    Livewire::test(\App\Livewire\Admin\PayPeriods\PayPeriodManager::class)
        ->call('toggleLock', $this->period->id);

    expect(PayPeriodLog::count())->toBe(1);

    $log = PayPeriodLog::first();
    expect($log->pay_period_id)->toBe($this->period->id);
    expect($log->user_id)->toBe($this->admin->id);
    expect($log->action)->toBe('unlocked');
    expect($log->note)->toContain('unlocked by');
    expect($log->note)->toContain($this->admin->name);
});

test('logAction helper creates log entry correctly', function () {
    expect(PayPeriodLog::count())->toBe(0);

    PayPeriodLog::logAction(
        $this->period,
        'test_action',
        'Test note',
        $this->admin->id
    );

    expect(PayPeriodLog::count())->toBe(1);

    $log = PayPeriodLog::first();
    expect($log->pay_period_id)->toBe($this->period->id);
    expect($log->user_id)->toBe($this->admin->id);
    expect($log->action)->toBe('test_action');
    expect($log->note)->toBe('Test note');
});

test('logAction uses authenticated user when userId is null', function () {
    $this->actingAs($this->admin);

    PayPeriodLog::logAction(
        $this->period,
        'test_action',
        'Test note'
    );

    $log = PayPeriodLog::first();
    expect($log->user_id)->toBe($this->admin->id);
});

test('pay period log has correct relationships', function () {
    PayPeriodLog::logAction(
        $this->period,
        'test_action',
        'Test note',
        $this->admin->id
    );

    $log = PayPeriodLog::with(['payPeriod', 'user'])->first();

    expect($log->payPeriod)->toBeInstanceOf(PayPeriod::class);
    expect($log->payPeriod->id)->toBe($this->period->id);
    expect($log->user)->toBeInstanceOf(User::class);
    expect($log->user->id)->toBe($this->admin->id);
});
