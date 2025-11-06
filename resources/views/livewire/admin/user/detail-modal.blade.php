<div>
    <x-modal title="User Details" wire:model="userDetailModal" box-class="bg-base-200 max-w-3xl">
        <x-form wire:submit="updateOrCreateDetail">
            <x-card shadow class="bg-base-100">
                <x-slot:title>Employment Details</x-slot:title>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-password label="SSS Number" wire:model="sss_number" />
                    <x-password label="TIN Number" wire:model="tin_number" />
                    <x-password label="Philhealth Number" wire:model="philhealth_number" />
                    <x-password label="Pag-IBIG Number" wire:model="pagibig_number" />
                    <x-input label="Hourly Rate" wire:model="hourly_rate" type="number" step="0.01"
                        prefix="₱" />
                    <x-input label="Position" wire:model="position" />
                    <x-select label="Gender" wire:model="gender" :options="$genders" />
                    @php
                        $civilStatuses = [
                            ['id' => 'single', 'name' => 'Single'],
                            ['id' => 'married', 'name' => 'Married'],
                            ['id' => 'divorced', 'name' => 'Divorced'],
                            ['id' => 'widowed', 'name' => 'Widowed'],
                        ];
                    @endphp
                    <x-select label="Civil Status" wire:model="civil_status" :options="$civilStatuses" />
                    <x-input label="Nationality" wire:model="nationality" />
                    <x-input label="Hire Date" wire:model="hire_date" type="date" />
                    <x-input label="Birthday" wire:model="birthday" type="date" />
                </div>
            </x-card>
            <x-slot:actions>
                <x-button wire:click="closeModal" label="Close" />
                <x-button label="Submit" type="submit" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
