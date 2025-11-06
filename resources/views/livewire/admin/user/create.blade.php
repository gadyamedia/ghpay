<div>
    <x-modal wire:model="createmodal" title="Create User" box-class="bg-base-200 max-w-3xl">
        <x-form wire:submit="create">
            @php
                $isAccountant = (string) ($role ?? '') === '4';
                $stepItems = $isAccountant
                    ? [
                        ['number' => 1, 'label' => 'User Info'],
                    ]
                    : [
                        ['number' => 1, 'label' => 'User Info'],
                        ['number' => 2, 'label' => 'Address'],
                        ['number' => 3, 'label' => 'Employment Details'],
                    ];
            @endphp

            <div class="space-y-6" wire:key="create-user-stepper-{{ $role ?? 'none' }}">
                <ul class="steps w-full [&>*:nth-child(2)]:before:hidden">
                    @foreach ($stepItems as $item)
                        <li
                            class="step {{ $step >= $item['number'] ? 'step-primary' : 'step-neutral' }}"
                            data-content="{{ $item['number'] }}"
                        >
                            {{ $item['label'] }}
                        </li>
                    @endforeach
                </ul>

                @if ($step === 1)
                    <x-card shadow class="bg-base-100" wire:key="step-panel-1">
                        <x-slot:title>User Info</x-slot:title>

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input label="Name" wire:model="name" />
                            <x-input label="Email" wire:model="email" type="email" />
                            <x-select label="Role" wire:model.live="role" :options="$roles" class="md:col-span-2" />
                            <x-input label="Password" wire:model.live="password" class="md:col-span-2">
                                <x-slot:append>
                                    <x-button label="Generate Password" class="join-item btn-primary"
                                        wire:click="generatePassword" type="button" />
                                </x-slot:append>
                            </x-input>

                            <x-toggle
                                label="Send invitation email"
                                wire:model="sendInvitation"
                                class="md:col-span-2"
                            />
                        </div>
                    </x-card>
                @endif

                @if (! $isAccountant && $step === 2)
                    <x-card shadow class="bg-base-100" wire:key="step-panel-2">
                        <x-slot:title>Address</x-slot:title>

                        <x-input label="Street" wire:model="street" />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-choices
                                label="Region"
                                wire:model.live="region"
                                :options="$regionsOptions"
                                placeholder="Search region..."
                                search-function="searchRegions"
                                single
                                searchable
                                clearable
                            />

                            <x-choices
                                label="Province"
                                wire:model.live="province"
                                :options="$provinceOptions"
                                placeholder="Search province..."
                                search-function="searchProvinces($wire.region)"
                                single
                                searchable
                                :disabled="! $region"
                                clearable
                            />

                            <x-choices
                                label="City"
                                wire:model.live="city"
                                :options="$cityOptions"
                                placeholder="Search city..."
                                search-function="searchCities($wire.province)"
                                single
                                searchable
                                :disabled="! $province"
                                clearable
                            />

                            <x-choices
                                label="Barangay"
                                wire:model.live="barangay"
                                :options="$barangayOptions"
                                placeholder="Search barangay..."
                                search-function="searchBarangays($wire.city)"
                                single
                                searchable
                                :disabled="! $city"
                                clearable
                            />
                        </div>
                    </x-card>
                @endif

                @if (! $isAccountant && $step === 3)
                    <x-card shadow class="bg-base-100" wire:key="step-panel-3">
                        <x-slot:title>Employment Details</x-slot:title>

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-password label="SSS Number" wire:model="sss_number" />
                            <x-password label="TIN Number" wire:model="tin_number" />
                            <x-password label="Philhealth Number" wire:model="philhealth_number" />
                            <x-password label="Pag-IBIG Number" wire:model="pagibig_number" />
                            <x-input label="Hourly Rate" wire:model="hourly_rate" type="number" step="0.01" prefix="₱" />
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
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.createmodal = false" type="button" />

                @if ($step > 1)
                    <x-button label="Previous" wire:click="prev" type="button" />
                @endif

                @if ($step < $maxStep)
                    <x-button label="Next" class="btn-primary" wire:click="next" type="button" />
                @else
                    <x-button label="Confirm" class="btn-primary" type="submit" />
                @endif
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
