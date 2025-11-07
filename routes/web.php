<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Admin\PayPeriods\PayPeriodManager;
use App\Livewire\Admin\PayPeriods\PayPeriodShow;
use App\Livewire\Admin\PayPeriods\PayslipForm;
use Livewire\Volt\Volt;

// Public typing test route (no auth required)
Route::get('/test/{token}', function (string $token) {
    return view('pages.typing-test', ['token' => $token]);
})->middleware('signed')->name('test.take');

// Public careers/jobs routes (no auth required)
Volt::route('/careers', 'careers.index')->name('careers.index');
Volt::route('/careers/{position}', 'careers.show')->name('careers.show');
Volt::route('/careers/{position}/apply', 'careers.apply')->name('careers.apply');
Volt::route('/privacy-policy', 'legal.privacypolicy')->name('privacy.policy');
Volt::route('/terms-of-service', 'legal.terms-of-service')->name('terms.of.service');

Route::middleware(['auth', 'verified'])->group(function () {
    // Root route - redirect based on user role
    Route::get('/', function () {
        if (auth()->user()->isRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('admin.pay-periods.index');
    })->name('home');

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/admin/pay-periods', PayPeriodManager::class)->name('admin.pay-periods.index');
    Route::get('/admin/payslips/{payslip}', PayslipForm::class)->name('admin.payslips.edit');
    Route::get('/admin/pay-periods/{period}', PayPeriodShow::class)->name('admin.pay-periods.show');

    Route::middleware('role:admin')->group(function () {
        Volt::route('/admin', 'admin.dashboard')->name('admin.dashboard');
        Route::prefix('admin')->group(function () {
            Volt::route('/users', 'admin.user.users')->name('admin.users');
            Volt::route('/candidates', 'admin.candidate-manager')->name('admin.candidates.index');
            Volt::route('/candidates/{candidateId}', 'admin.candidate-results')->name('admin.candidates.show');
            Volt::route('/typing-samples', 'admin.typing-text-samples')->name('admin.typing-samples.index');
            Volt::route('/positions', 'admin.positions.index')->name('admin.positions.index');
            Volt::route('/applications', 'admin.applications.index')->name('admin.applications.index');
        });
    });

});

Route::middleware(['guest'])->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('/forgot-password', 'auth.passwordreset')->name('password.reset');

});
