<?php

declare(strict_types=1);

use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
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
    $this->employee = User::factory()->create();
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

test('admin can acknowledge sent payslip', function () {
    $this->payslip->update(['status' => 'sent', 'sent_at' => now()]);

    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('acknowledgePayslip')
        ->assertDispatched('toast', type: 'success', message: 'Payslip marked as acknowledged.');

    $this->payslip->refresh();

    expect($this->payslip->status)->toBe('acknowledged');
    expect($this->payslip->acknowledged_at)->not->toBeNull();
    expect($this->payslip->acknowledged_by)->toBe($this->admin->id);
});

test('cannot acknowledge draft payslip', function () {
    expect($this->payslip->status)->toBe('draft');

    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('acknowledgePayslip')
        ->assertDispatched('toast', type: 'error', message: 'Only sent payslips can be acknowledged.');

    $this->payslip->refresh();

    expect($this->payslip->status)->toBe('draft');
    expect($this->payslip->acknowledged_at)->toBeNull();
});

test('cannot acknowledge already acknowledged payslip', function () {
    $firstAcknowledger = $this->admin;
    $firstTime = now()->subHour();

    $this->payslip->update([
        'status' => 'acknowledged',
        'acknowledged_at' => $firstTime,
        'acknowledged_by' => $firstAcknowledger->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('acknowledgePayslip')
        ->assertDispatched('toast', type: 'error', message: 'Only sent payslips can be acknowledged.');

    $this->payslip->refresh();

    expect($this->payslip->acknowledged_at->timestamp)->toBe($firstTime->timestamp);
    expect($this->payslip->acknowledged_by)->toBe($firstAcknowledger->id);
});

test('acknowledged payslip shows success alert', function () {
    $this->payslip->update([
        'status' => 'acknowledged',
        'acknowledged_at' => now(),
        'acknowledged_by' => $this->admin->id,
    ]);

    $this->payslip->load('acknowledgedBy');

    $this->actingAs($this->admin);

    Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->assertSee('Payslip Acknowledged');
});

test('acknowledge button only appears for sent payslips', function () {
    $this->actingAs($this->admin);

    // Draft payslip should not show acknowledge button
    Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->assertDontSee('Mark as Acknowledged');

    // Sent payslip should show acknowledge button
    $this->payslip->update(['status' => 'sent', 'sent_at' => now()]);

    Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->assertSee('Mark as Acknowledged');
});

test('send button is disabled after payslip is sent', function () {
    $this->payslip->update(['status' => 'sent', 'sent_at' => now()]);

    $this->actingAs($this->admin);

    $component = Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip]);

    $html = $component->html();

    expect($html)->toContain('disabled');
});

test('send button is disabled after payslip is acknowledged', function () {
    $this->payslip->update([
        'status' => 'acknowledged',
        'acknowledged_at' => now(),
        'acknowledged_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip]);

    $html = $component->html();

    expect($html)->toContain('disabled');
});
