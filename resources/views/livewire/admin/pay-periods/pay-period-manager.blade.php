<div>
    <x-header title="Pay Periods" icon="o-currency-dollar" with-anchor separator
        class="pt-1">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" icon="o-plus" label="Create Pay Period" class="btn-secondary" />
            <x-button wire:click="generateDefault" icon="o-calendar-days" label="Generate Standard Periods" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <table class="table w-full bg-white rounded-md">
        <thead>
            <tr>
                <th>Label</th>
                <th>Dates</th>
                <th>Pay Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($periods as $period)
                <tr wire:click="selectPeriod({{ $period->id }})">
                    <td class="font-semibold">{{ $period->display_label }}</td>
                    <td>
                        {{ $period->start_date->format('M d') }} – {{ $period->end_date->format('M d, Y') }}
                    </td>
                    <td>{{ $period->pay_date?->format('M d, Y') ?? '—' }}</td>
                    <td>
                        <x-icon :name="$period->is_locked ? 'o-lock-closed' : 'o-lock-open'" :class="$period->is_locked ? 'text-red-500' : 'text-green-500'" />
                    </td>
                    <td>
                        <x-button wire:click="toggleLock({{ $period->id }})" :label="$period->is_locked ? 'Unlock' : 'Lock'" :color="$period->is_locked ? 'yellow' : 'gray'"
                            class="btn-xs btn-secondary" />
                        <x-button wire:click="showLogs({{ $period->id }})" label="Logs" class="btn-xs btn-primary" />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Create Modal --}}
    <x-modal wire:model="showCreateModal" title="Create Pay Period" box-class="max-w-xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-datetime label="Start Date" wire:model="start_date" type="date" />
            <x-datetime label="End Date" wire:model="end_date" type="date" />
            <x-datetime label="Pay Date" wire:model="pay_date" type="date" />
            <x-input label="Label (optional)" wire:model="label" class="md:col-span-2" />
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <x-button wire:click="$set('showCreateModal', false)" label="Cancel" />
            <x-button wire:click="createPeriod" color="green" label="Create" />
        </div>
    </x-modal>

    {{-- Logs Modal --}}
    <x-modal wire:model="showLogsModal" title="Activity Logs" box-class="max-w-2xl">
        <div class="space-y-3">
            @forelse($logs as $log)
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-2 w-2 rounded-full bg-base-content/60"></div>
                    <div>
                        <div class="text-sm">
                            <span class="font-semibold">{{ $log->user->name }}</span>
                            <span class="opacity-70"> {{ $log->action }}</span>
                        </div>
                        <div class="text-xs opacity-70">{{ $log->created_at->format('M d, Y H:i') }}</div>
                        @if ($log->note)
                            <div class="text-xs mt-1">{{ $log->note }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-sm opacity-70">No logs yet.</div>
            @endforelse
        </div>
        <div class="mt-6 flex justify-end">
            <x-button wire:click="$set('showLogsModal', false)" label="Close" />
        </div>
    </x-modal>
</div>
