<?php

use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDetail;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Create admin role
    $adminRole = Role::create(['name' => 'admin']);

    // Create admin user with detail
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    UserDetail::create([
        'user_id' => $admin->id,
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
    $employee = User::factory()->create();
    UserDetail::create([
        'user_id' => $employee->id,
        'hourly_rate' => 100.00,
        'position' => 'Employee',
        'gender' => 'female',
        'civil_status' => 'married',
        'nationality' => 'Filipino',
        'hire_date' => now()->subMonths(6),
        'birthday' => now()->subYears(25),
        'pagibig' => '111111111',
        'sss' => '222222222',
        'tin' => '333333333',
        'philhealth' => '444444444',
        'street' => 'Employee Street',
    ]);

    // Create pay period
    $period = PayPeriod::create([
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->startOfMonth()->addDays(14),
        'pay_date' => now()->startOfMonth()->addDays(17),
        'label' => 'Test Period',
        'is_locked' => false,
    ]);

    // Create payslip
    $payslip = Payslip::create([
        'user_id' => $employee->id,
        'pay_period_id' => $period->id,
    ]);

    $this->admin = $admin;
    $this->employee = $employee;
    $this->period = $period;
    $this->payslip = $payslip;
});

test('admin can view payslip form for unlocked period', function () {
    actingAs($this->admin)
        ->get(route('admin.payslips.edit', $this->payslip))
        ->assertOk()
        ->assertSeeLivewire(\App\Livewire\Admin\PayPeriods\PayslipForm::class);
});

test('payslip form shows warning on mount when period is locked', function () {
    $this->period->update(['is_locked' => true]);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->assertDispatched('toast', type: 'warning', message: 'This pay period is locked. Changes cannot be saved.');
});

test('cannot save draft when period is locked', function () {
    $this->period->update(['is_locked' => true]);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('saveDraft')
        ->assertDispatched('toast', type: 'error', message: 'Cannot save. Pay period is locked.');
});

test('can save draft when period is unlocked', function () {
    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->set('earnings.0.label', 'Basic Salary')
        ->set('earnings.0.hours', 80)
        ->call('saveDraft')
        ->assertDispatched('toast', type: 'success', message: 'Payslip saved.');

    expect($this->payslip->fresh()->earnings()->count())->toBeGreaterThan(0);
});

test('cannot send payslip when period is locked', function () {
    $this->period->update(['is_locked' => true]);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('sendPayslip')
        ->assertDispatched('toast', type: 'error', message: 'Cannot send. Pay period is locked.');
});

test('cannot add earning row when period is locked', function () {
    $this->period->update(['is_locked' => true]);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('addEarning')
        ->assertDispatched('toast', type: 'warning', message: 'Cannot add rows. Pay period is locked.');
});

test('cannot remove earning row when period is locked', function () {
    $this->period->update(['is_locked' => true]);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('removeEarning', 0)
        ->assertDispatched('toast', type: 'warning', message: 'Cannot remove rows. Pay period is locked.');
});

test('cannot add deduction row when period is locked', function () {
    $this->period->update(['is_locked' => true]);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('addDeduction')
        ->assertDispatched('toast', type: 'warning', message: 'Cannot add rows. Pay period is locked.');
});

test('cannot remove deduction row when period is locked', function () {
    $this->period->update(['is_locked' => true]);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('removeDeduction', 0)
        ->assertDispatched('toast', type: 'warning', message: 'Cannot remove rows. Pay period is locked.');
});

test('can download pdf for locked period', function () {
    $this->period->update(['is_locked' => true]);

    $component = Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\PayPeriods\PayslipForm::class, ['payslip' => $this->payslip])
        ->call('downloadPreview');

    // Component should dispatch a warning toast but still allow download
    $component->assertDispatched('toast', type: 'warning', message: 'Pay period is locked. Showing last saved version.');
});
