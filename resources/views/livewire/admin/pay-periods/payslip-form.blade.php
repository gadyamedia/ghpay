<div class="space-y-6">
    <x-header title="Payslip Editor" :subtitle="$user->name .
        ' — ' .
        ($period->display_label ?? $period->start_date->format('M d') . ' – ' . $period->end_date->format('M d, Y'))">
        <x-slot:actions>
            <x-input label="Employee" :value="$user->name" disabled />
            <x-input label="Position" :value="$user->detail->position ?? ''" disabled />
            <x-input label="Hourly Rate" :value="number_format($user->detail->hourly_rate ?? 0, 2)" disabled />
        </x-slot:actions>

    </x-header>

    @if ($period->is_locked)
        <x-alert title="This pay period is locked" icon="o-lock-closed" class="alert-warning">
            You cannot edit, save, or send this payslip. Download PDF to view the last saved version.
        </x-alert>
    @endif

    @if ($payslip->status === 'acknowledged')
        <x-alert title="Payslip Acknowledged" icon="o-check-circle" class="alert-success">
            This payslip was acknowledged on {{ $payslip->acknowledged_at->format('M d, Y g:i A') }}
            by {{ $payslip->acknowledgedBy->name ?? 'Unknown' }}.
        </x-alert>
    @endif

    {{-- Earnings --}}
    <x-card>
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold text-lg">Earnings</h3>
            <x-button icon="o-plus" wire:click="addEarning" label="Add Earning" :disabled="$period->is_locked" />
        </div>

        <div class="space-y-3">
            @foreach ($earnings as $i => $row)
                <div class="grid grid-cols-12 gap-3 items-end">
                    <div class="col-span-4">
                        <x-input label="Label" wire:model="earnings.{{ $i }}.label" :disabled="$period->is_locked" />
                    </div>
                    <div class="col-span-2">
                        <x-input label="Rate" type="number" step="0.01"
                            wire:model.lazy="earnings.{{ $i }}.rate" :disabled="$period->is_locked" />
                    </div>
                    <div class="col-span-2">
                        <x-input label="Hours" type="number" step="0.01"
                            wire:model.lazy="earnings.{{ $i }}.hours" :disabled="$period->is_locked" />
                    </div>
                    <div class="col-span-3">
                        <x-input label="Amount" type="number" step="0.01"
                            wire:model.lazy="earnings.{{ $i }}.amount" :disabled="$period->is_locked" />
                    </div>
                    <div class="col-span-1 flex justify-end">
                        <x-button color="red" icon="o-trash" wire:click="removeEarning({{ $i }})" :disabled="$period->is_locked" />
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>

    {{-- Deductions --}}
    <x-card>
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold text-lg">Deductions</h3>
            <x-button icon="o-plus" wire:click="addDeduction" label="Add Deduction" :disabled="$period->is_locked" />
        </div>

        <div class="space-y-3">
            @foreach ($deductions as $i => $row)
                <div class="grid grid-cols-12 gap-3 items-end">
                    <div class="col-span-7">
                        <x-input label="Label" wire:model="deductions.{{ $i }}.label"
                            placeholder="SSS, PhilHealth, Pag-IBIG, Tax, Loans…" :disabled="$period->is_locked" />
                    </div>
                    <div class="col-span-4">
                        <x-input label="Amount" type="number" step="0.01"
                            wire:model.lazy="deductions.{{ $i }}.amount" :disabled="$period->is_locked" />
                    </div>
                    <div class="col-span-1 flex justify-end">
                        <x-button color="red" icon="o-trash" wire:click="removeDeduction({{ $i }})" :disabled="$period->is_locked" />
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>

    {{-- Lates & Absences --}}
    <x-card>
        <h3 class="font-semibold text-lg mb-3">Lates & Absences</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input label="Late (hours)" type="number" step="0.01" wire:model.lazy="late_hours" :disabled="$period->is_locked" />
            <x-input label="Absences (days)" type="number" step="0.01" wire:model.lazy="absence_days" :disabled="$period->is_locked" />
        </div>

        <div class="mt-3">
            <x-checkbox wire:model.live="override_deductions" label="Override automatic deduction calculation" :disabled="$period->is_locked" />
        </div>

        @if ($override_deductions)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                <x-input label="Late Deduction (₱)" type="number" step="0.01" wire:model.lazy="late_deduction" :disabled="$period->is_locked" />
                <x-input label="Absence Deduction (₱)" type="number" step="0.01"
                    wire:model.lazy="absence_deduction" :disabled="$period->is_locked" />
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 opacity-70">
                <x-input label="Late Deduction (auto)" :value="number_format($late_deduction, 2)" disabled />
                <x-input label="Absence Deduction (auto)" :value="number_format($absence_deduction, 2)" disabled />
            </div>
        @endif
    </x-card>

    {{-- Summary & Actions --}}
    <x-card>
        <div class="space-y-1">
            <div class="flex justify-between text-sm">
                <span>Gross Earnings</span>
                <span>₱{{ number_format($gross, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span>Total Deductions</span>
                <span>₱{{ number_format($total_deductions, 2) }}</span>
            </div>
            <div class="flex justify-between font-semibold border-t pt-2 mt-2">
                <span>Net Salary</span>
                <span>₱{{ number_format($net, 2) }}</span>
            </div>
        </div>

        <div class="mt-5 flex justify-end gap-3">
            <x-button wire:click="saveDraft" label="Save Draft" :disabled="$period->is_locked" />
            <x-button class="btn-primary text-white" wire:click="sendPayslip" icon="o-envelope" label="Send Payslip" :disabled="$period->is_locked || $payslip->status === 'sent' || $payslip->status === 'acknowledged'" />
            @if($payslip->status === 'sent')
                <x-button class="btn-success text-white" wire:click="acknowledgePayslip" icon="o-check-circle" label="Mark as Acknowledged" />
            @endif
            <x-button class="btn-secondary" wire:click="downloadPreview" icon="o-arrow-down-tray" label="Download PDF" />
        </div>
    </x-card>
</div>
