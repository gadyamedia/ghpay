<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Admin\PayPeriods\PayPeriodManager;
use App\Livewire\Admin\PayPeriods\PayPeriodShow;
use App\Livewire\Admin\PayPeriods\PayslipForm;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('/', 'users.index');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/admin/pay-periods', PayPeriodManager::class)->name('admin.pay-periods.index');
    Route::get('/admin/payslips/{payslip}', PayslipForm::class)->name('admin.payslips.edit');
    Route::get('/admin/pay-periods/{period}', PayPeriodShow::class)->name('admin.pay-periods.show');

    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->group(function () {
            Volt::route('/users', 'admin.user.users')->name('admin.users');
        });
    });

});

Route::middleware(['guest'])->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('/forgot-password', 'auth.passwordreset')->name('password.reset');

});
