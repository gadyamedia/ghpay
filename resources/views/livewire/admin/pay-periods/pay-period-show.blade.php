<div class="space-y-6">
    <x-header :title="$period->display_label" :subtitle="'Pay Date: ' . ($period->pay_date?->format('M d, Y') ?? '—')" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input type="search" placeholder="Search employee" wire:model.debounce.400ms="search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-select :options="[
                ['id' => 'all', 'name' => 'All'],
                ['id' => 'draft', 'name' => 'Draft'],
                ['id' => 'sent', 'name' => 'Sent'],
                ['id' => 'acknowledged', 'name' => 'Acknowledged'],
            ]" option-value="id" option-label="name" wire:model="status" />
            <x-button icon="o-plus" label="Create Payslip" wire:click="openCreateModal" class="btn-secondary" />
            <x-button icon="o-users" label="Bulk Generate" wire:click="bulkGenerate" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div>
        <x-icon :name="$period->is_locked ? 'o-lock-closed' : 'o-lock-open'" :class="$period->is_locked ? 'text-red-500' : 'text-green-500'" />
        <span class="ml-2 opacity-70 text-sm">
            Total payslips: {{ $period->payslips_count }}
        </span>
    </div>

    {{-- Payslips table --}}
    <table class="table w-full bg-white rounded-md mb-4">
        <thead>
            <tr>
                <th>Employee</th>
                <th class="text-right">Gross</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Net</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payslips as $p)
                <tr wire:key="row-{{ $p->id }}">
                    <td class="font-medium">
                        {{ $p->user->name }}
                        <div class="text-xs opacity-60">{{ $p->user->email }}</div>
                    </td>
                    <td class="text-right">₱{{ number_format($p->gross_earnings, 2) }}</td>
                    <td class="text-right">₱{{ number_format($p->total_deductions, 2) }}</td>
                    <td class="text-right font-semibold">₱{{ number_format($p->net_salary, 2) }}</td>
                    <td>
                        <x-badge :color="match ($p->status) {
                            'draft' => 'gray',
                            'sent' => 'blue',
                            'acknowledged' => 'green',
                            default => 'gray',
                        }" value="{{ ucfirst($p->status) }}"/>
                    </td>
                    <td class="text-right space-x-2">
                        <a href="{{ route('admin.payslips.edit', $p) }}">
                            <x-button size="sm" label="Open" />
                        </a>
                        @if (!$period->is_locked && $p->status === 'draft')
                            <x-button size="sm" color="red" label="Delete"
                                wire:click="deleteDraft('{{ $p->id }}')" />
                        @endif
                    </td>
                </tr>
            @endforeach
            @if ($payslips->isEmpty())
                <tr>
                    <td colspan="6" class="text-center opacity-60">
                        No payslips yet for this period.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Create Payslip Modal --}}
    <x-modal wire:model="showCreateModal" title="Create payslip" box-class="max-w-xl">
        <div class="space-y-4">
            <x-select
                label="Employee"
                wire:model.live="user_id"
                :options="$eligibleUsers"
                option-value="id"
                option-label="name"
                searchable
                placeholder="Select employee"
                hint="Only users with UserDetail are listed. Use toggle below to include users who already have a payslip."
            />

            <x-checkbox wire:model="includeUsersWithExistingPayslip"
                label="Include users who already have a payslip for this period" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-button wire:click="$set('showCreateModal', false)" label="Cancel" />
            <x-button color="green" wire:click="createForUser" label="Create & Open" />
        </div>
    </x-modal>
</div>
