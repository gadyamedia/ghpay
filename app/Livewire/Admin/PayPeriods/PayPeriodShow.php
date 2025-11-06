<?php

namespace App\Livewire\Admin\PayPeriods;

use App\Models\PayPeriod;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pay Period Details')]
class PayPeriodShow extends Component
{
    use WithPagination;

    public PayPeriod $period;

    // UI state
    public bool $showCreateModal = false;

    public ?string $user_id = null;

    // filters
    public string $status = 'all'; // all | draft | sent | acknowledged

    public string $search = '';

    // for bulk create
    public bool $includeUsersWithExistingPayslip = false;

    public function mount(PayPeriod $period): void
    {
        Gate::authorize('manage-payroll');
        $this->period = $period->loadCount('payslips');
    }

    /** Users that have UserDetail (active employees), optionally exclude already-created payslips */
    protected function eligibleUsers()
    {
        $users = User::query()
            ->with('detail')
            ->whereHas('detail') // active if they have details; tweak to your “active” rule
            ->when(! $this->includeUsersWithExistingPayslip, function (Builder $q) {
                $q->whereDoesntHave('payslips', function (Builder $p) {
                    $p->where('pay_period_id', $this->period->id);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return $users;
    }

    public function openCreateModal(): void
    {
        if ($this->period->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'This period is locked.');

            return;
        }
        $this->user_id = null;
        $this->showCreateModal = true;
    }

    public function createForUser(): void
    {
        if ($this->period->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'This period is locked.');

            return;
        }

        $this->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $payslip = Payslip::firstOrCreate([
            'user_id' => $this->user_id,
            'pay_period_id' => $this->period->id,
        ]);

        $this->showCreateModal = false;
        $this->dispatch('toast', type: 'success', message: 'Payslip created.');
        // Redirect straight to editor
        $this->redirectRoute('admin.payslips.edit', $payslip);
    }

    public function bulkGenerate(): void
    {
        if ($this->period->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'This period is locked.');

            return;
        }

        $count = 0;
        foreach ($this->eligibleUsers() as $user) {
            Payslip::firstOrCreate([
                'user_id' => $user->id,
                'pay_period_id' => $this->period->id,
            ]);
            $count++;
        }

        $this->dispatch('toast', type: 'success', message: "Generated payslips for {$count} user(s).");
        $this->resetPage(); // refresh pagination
    }

    public function deleteDraft(string $payslipId): void
    {
        if ($this->period->is_locked) {
            $this->dispatch('toast', type: 'warning', message: 'This period is locked.');

            return;
        }

        $p = Payslip::where('id', $payslipId)
            ->where('pay_period_id', $this->period->id)
            ->where('status', 'draft')
            ->first();

        if (! $p) {
            $this->dispatch('toast', type: 'warning', message: 'Only draft payslips can be deleted.');

            return;
        }

        $p->earnings()->delete();
        $p->deductions()->delete();
        $p->delete();

        $this->dispatch('toast', type: 'success', message: 'Draft payslip deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $q = Payslip::query()
            ->with(['user.detail'])
            ->where('pay_period_id', $this->period->id)
            ->when($this->status !== 'all', fn (Builder $qq) => $qq->where('status', $this->status))
            ->when($this->search !== '', function (Builder $qq) {
                $term = "%{$this->search}%";
                $qq->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderByDesc('created_at');

        return view('livewire.admin.pay-periods.pay-period-show', [
            'payslips' => $q->paginate(15),
            'eligibleUsers' => $this->eligibleUsers(),
        ]);
    }
}
