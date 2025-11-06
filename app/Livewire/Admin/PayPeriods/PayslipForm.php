<?php

namespace App\Livewire\Admin\PayPeriods;

use App\Models\PayPeriodLog;
use App\Models\Payslip;
use App\Models\PayslipDeduction;
use App\Models\PayslipEarning;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Payslip Form')]
class PayslipForm extends Component
{
    public Payslip $payslip;

    // Editable arrays for rows
    public array $earnings = [];

    public array $deductions = [];

    // Lates/Absences and override
    public $late_hours = 0.00;

    public $absence_days = 0.00;

    public $override_deductions = false;

    public $late_deduction = 0.00;

    public $absence_deduction = 0.00;

    // Summaries
    public $gross = 0.00;

    public $total_deductions = 0.00;

    public $net = 0.00;

    public function mount(Payslip $payslip): void
    {
        Gate::authorize('manage-payroll');

        $this->payslip = $payslip->load(['user.detail', 'earnings', 'deductions', 'payPeriod']);

        if ($this->payslip->payPeriod->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'This pay period is locked. Changes cannot be saved.');
        }

        $this->earnings = $this->payslip->earnings->map(fn ($e) => $e->only(['id', 'label', 'rate', 'hours', 'amount']))->toArray();
        $this->deductions = $this->payslip->deductions->map(fn ($d) => $d->only(['id', 'label', 'amount']))->toArray();

        if (empty($this->earnings)) {
            $this->earnings = $this->defaultEarningsRows();
        }

        if (empty($this->deductions)) {
            $this->deductions = $this->defaultDeductionRows();
        }

        $this->late_hours = (float) $this->payslip->late_hours;
        $this->absence_days = (float) $this->payslip->absence_days;
        $this->override_deductions = (bool) $this->payslip->override_deductions;
        $this->late_deduction = (float) $this->payslip->late_deduction;
        $this->absence_deduction = (float) $this->payslip->absence_deduction;

        $this->recalculate();
    }

    /*** Row actions ***/
    public function addEarning(): void
    {
        if ($this->payslip->payPeriod->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'Cannot add rows. Pay period is locked.');

            return;
        }

        $this->earnings[] = ['label' => '', 'rate' => null, 'hours' => null, 'amount' => 0];
        $this->recalculate();
    }

    public function removeEarning($index): void
    {
        if ($this->payslip->payPeriod->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'Cannot remove rows. Pay period is locked.');

            return;
        }

        unset($this->earnings[$index]);
        $this->earnings = array_values($this->earnings);
        $this->recalculate();
    }

    public function addDeduction(): void
    {
        if ($this->payslip->payPeriod->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'Cannot add rows. Pay period is locked.');

            return;
        }

        $this->deductions[] = ['label' => '', 'amount' => 0];
        $this->recalculate();
    }

    public function removeDeduction($index): void
    {
        if ($this->payslip->payPeriod->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'Cannot remove rows. Pay period is locked.');

            return;
        }

        unset($this->deductions[$index]);
        $this->deductions = array_values($this->deductions);
        $this->recalculate();
    }

    public function updated($field): void
    {
        // Any field change triggers recompute
        if (str_starts_with($field, 'earnings') ||
            str_starts_with($field, 'deductions') ||
            in_array($field, ['late_hours', 'absence_days', 'override_deductions', 'late_deduction', 'absence_deduction'])) {
            $this->recalculate();
        }
    }

    private function recalculate(): void
    {
        $baseRate = (float) ($this->payslip->user->detail->hourly_rate ?? 0);

        $this->earnings = array_map(function ($row) use ($baseRate) {
            $hours = isset($row['hours']) ? (float) $row['hours'] : null;
            $multiplier = isset($row['rate']) ? (float) $row['rate'] : null;

            if ($hours === null) {
                return $row;
            }

            if ($multiplier === null || $multiplier === 0.0) {
                $row['amount'] = round($baseRate * $hours, 2);

                return $row;
            }

            $row['amount'] = round($baseRate * $multiplier * $hours, 2);

            return $row;
        }, $this->earnings);

        // 2) compute lates/absences unless overridden
        $dailyRate = $baseRate * 8;

        if (! $this->override_deductions) {
            $this->late_deduction = round((float) $this->late_hours * $baseRate, 2);
            $this->absence_deduction = round((float) $this->absence_days * $dailyRate, 2);
        }

        // 3) totals
        $this->gross = round(collect($this->earnings)->sum('amount'), 2);
        $this->total_deductions = round(
            collect($this->deductions)->sum('amount') + $this->late_deduction + $this->absence_deduction,
            2
        );
        $this->net = round($this->gross - $this->total_deductions, 2);
    }

    /*** Persist ***/
    public function saveDraft(): void
    {
        if ($this->payslip->payPeriod->is_locked) {
            $this->dispatch('toast', type: 'error', message: 'Cannot save. Pay period is locked.');

            return;
        }

        $this->validate([
            'earnings.*.label' => ['required', 'string', 'max:120'],
            'earnings.*.amount' => ['required', 'numeric', 'min:0'],
            'deductions.*.label' => ['nullable', 'string', 'max:120'],
            'deductions.*.amount' => ['nullable', 'numeric', 'min:0'],
            'late_hours' => ['nullable', 'numeric', 'min:0'],
            'absence_days' => ['nullable', 'numeric', 'min:0'],
            'late_deduction' => ['nullable', 'numeric', 'min:0'],
            'absence_deduction' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Snapshot of user + detail for historical accuracy
        if (empty($this->payslip->user_snapshot)) {
            $this->payslip->user_snapshot = $this->payslip->user->load('detail')->toArray();
        }

        // Save primary
        $this->payslip->fill([
            'gross_earnings' => $this->gross,
            'total_deductions' => $this->total_deductions,
            'net_salary' => $this->net,
            'late_hours' => $this->late_hours,
            'absence_days' => $this->absence_days,
            'late_deduction' => $this->late_deduction,
            'absence_deduction' => $this->absence_deduction,
            'override_deductions' => $this->override_deductions,
        ])->save();

        // Sync earnings
        $existingEarningIds = [];
        foreach ($this->earnings as $index => $row) {
            $model = null;
            if (! empty($row['id'])) {
                $model = PayslipEarning::find($row['id']);
                if ($model && $model->payslip_id !== $this->payslip->id) {
                    $model = null;
                }
            }
            $data = [
                'payslip_id' => $this->payslip->id,
                'label' => $row['label'],
                'rate' => $row['rate'] ?? null,
                'hours' => $row['hours'] ?? null,
                'amount' => $row['amount'] ?? 0,
            ];
            $model = $model ? tap($model)->update($data) : PayslipEarning::create($data);
            $existingEarningIds[] = $model->id;
            $this->earnings[$index]['id'] = $model->id;
        }
        PayslipEarning::where('payslip_id', $this->payslip->id)
            ->whereNotIn('id', $existingEarningIds)->delete();

        // Sync deductions
        $existingDeductionIds = [];
        foreach ($this->deductions as $index => $row) {
            // Skip empty rows
            if ((string) ($row['label'] ?? '') === '' && (float) ($row['amount'] ?? 0) === 0.0) {
                continue;
            }

            $model = null;
            if (! empty($row['id'])) {
                $model = PayslipDeduction::find($row['id']);
                if ($model && $model->payslip_id !== $this->payslip->id) {
                    $model = null;
                }
            }
            $data = [
                'payslip_id' => $this->payslip->id,
                'label' => $row['label'] ?? '',
                'amount' => $row['amount'] ?? 0,
            ];
            $model = $model ? tap($model)->update($data) : PayslipDeduction::create($data);
            $existingDeductionIds[] = $model->id;
            $this->deductions[$index]['id'] = $model->id;
        }
        PayslipDeduction::where('payslip_id', $this->payslip->id)
            ->whereNotIn('id', $existingDeductionIds)->delete();

        $this->dispatch('toast', type: 'success', message: 'Payslip saved.');
    }

    private function defaultEarningsRows(): array
    {
        return [
            ['label' => 'Basic Salary', 'rate' => null, 'hours' => null, 'amount' => 0],
            ['label' => 'OT - Regular Day', 'rate' => null, 'hours' => null, 'amount' => 0],
            ['label' => 'OT - Rest Day', 'rate' => null, 'hours' => null, 'amount' => 0],
            ['label' => 'OT - Regular Holiday', 'rate' => null, 'hours' => null, 'amount' => 0],
            ['label' => 'OT - Special Holiday', 'rate' => null, 'hours' => null, 'amount' => 0],
        ];
    }

    private function defaultDeductionRows(): array
    {
        return [
            ['label' => 'SSS', 'amount' => 0],
            ['label' => 'PhilHealth', 'amount' => 0],
            ['label' => 'Pag-IBIG', 'amount' => 0],
            ['label' => 'Withholding Tax', 'amount' => 0],
            ['label' => 'Loan', 'amount' => 0],
        ];
    }

    public function downloadPreview(): StreamedResponse
    {
        if ($this->payslip->payPeriod->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'Pay period is locked. Showing last saved version.');
        }

        // Save first to ensure data is current (only if unlocked)
        if (! $this->payslip->payPeriod->is_locked) {
            $this->saveDraft();
        }

        // Generate PDF using your payslip HTML template (render it with the snapshot)
        $html = view('pdf.payslip', [
            'payslip' => $this->payslip->load(['user', 'earnings', 'deductions', 'payPeriod']),
        ])->render();

        $pdf = PDF::loadHTML($html)->setPaper('A4');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "Payslip_{$this->payslip->user->name}_{$this->payslip->payPeriod->label}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    public function sendPayslip(): void
    {
        if ($this->payslip->payPeriod->is_locked) {
            $this->dispatch('toast', type: 'error', message: 'Cannot send. Pay period is locked.');

            return;
        }

        // Save first to ensure data is current
        $this->saveDraft();

        // Dispatch the job to send the payslip email with PDF
        \App\Jobs\SendPayslipJob::dispatch($this->payslip->id);

        $this->dispatch('toast', type: 'success', message: 'Payslip is being sent via email...');
    }

    public function acknowledgePayslip(): void
    {
        Gate::authorize('manage-payroll');

        if ($this->payslip->status !== 'sent') {
            $this->dispatch('toast', type: 'error', message: 'Only sent payslips can be acknowledged.');

            return;
        }

        $this->payslip->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => auth()->id(),
        ]);

        // Log the acknowledgement action
        PayPeriodLog::logAction(
            $this->payslip->payPeriod,
            'payslip_acknowledged',
            "Payslip for {$this->payslip->user->name} marked as acknowledged"
        );

        $this->dispatch('toast', type: 'success', message: 'Payslip marked as acknowledged.');
    }

    public function render()
    {
        return view('livewire.admin.pay-periods.payslip-form', [
            'user' => $this->payslip->user,
            'period' => $this->payslip->payPeriod,
        ]);
    }
}
