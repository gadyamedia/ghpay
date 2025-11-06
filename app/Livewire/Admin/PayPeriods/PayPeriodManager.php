<?php

namespace App\Livewire\Admin\PayPeriods;

use App\Models\PayPeriod;
use App\Models\PayPeriodLog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

#[Title('Pay Periods')]
class PayPeriodManager extends Component
{
    use Toast;

    public $showCreateModal = false;

    public $showLogsModal = false;

    public $logs = [];

    // Create form
    public $start_date;

    public $end_date;

    public $pay_date;

    public $label;

    public function mount(): void
    {
        Gate::authorize('manage-payroll');
    }

    public function openCreateModal(): void
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function createPeriod(): void
    {
        $this->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['nullable', 'date'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $period = PayPeriod::create([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'pay_date' => $this->pay_date,
            'label' => $this->label,
        ]);

        PayPeriodLog::create([
            'pay_period_id' => $period->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'note' => 'Manual create',
        ]);

        $this->showCreateModal = false;

        $this->toast('success', 'Pay period created.');
    }

    public function generateDefault(): void
    {
        // Create two standard periods for the current month
        $now = now();

        $p1 = PayPeriod::firstOrCreate([
            'start_date' => $now->copy()->startOfMonth()->toDateString(),
            'end_date' => $now->copy()->startOfMonth()->addDays(14)->toDateString(),
            'pay_date' => $now->copy()->startOfMonth()->addDays(17)->toDateString(), // 18th
        ], [
            'label' => $now->format('F Y').' 1–15',
        ]);

        $p2 = PayPeriod::firstOrCreate([
            'start_date' => $now->copy()->startOfMonth()->addDays(15)->toDateString(),
            'end_date' => $now->copy()->endOfMonth()->toDateString(),
            'pay_date' => $now->copy()->addMonth()->startOfMonth()->addDays(2)->toDateString(), // 3rd
        ], [
            'label' => $now->format('F Y').' 16–'.$now->endOfMonth()->day,
        ]);

        foreach ([$p1, $p2] as $p) {
            PayPeriodLog::firstOrCreate([
                'pay_period_id' => $p->id,
                'user_id' => auth()->id(),
                'action' => 'created',
            ]);
        }

        $this->toast('success', 'Standard periods generated.');
    }

    public function toggleLock(int $id): void
    {
        $period = PayPeriod::findOrFail($id);
        $wasLocked = $period->is_locked;
        $period->is_locked = ! $period->is_locked;
        $period->save();

        // Log the lock/unlock action
        PayPeriodLog::logAction(
            $period,
            $period->is_locked ? 'locked' : 'unlocked',
            $period->is_locked
                ? 'Pay period locked by '.auth()->user()->name
                : 'Pay period unlocked by '.auth()->user()->name
        );

        $this->toast('success', $period->is_locked ? 'Period locked.' : 'Period unlocked.');
    }

    public function selectPeriod(int $id): void
    {
        $this->redirectRoute('admin.pay-periods.show', ['period' => $id]);
    }

    public function showLogs(int $id): void
    {
        $this->logs = PayPeriod::with(['logs.user'])->findOrFail($id)->logs()->latest()->get();
        $this->showLogsModal = true;
    }

    private function resetCreateForm(): void
    {
        $this->start_date = null;
        $this->end_date = null;
        $this->pay_date = null;
        $this->label = null;
    }

    public function render()
    {
        return view('livewire.admin.pay-periods.pay-period-manager', [
            'periods' => PayPeriod::query()->latest('start_date')->get(),
        ]);
    }
}
