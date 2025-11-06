<div>
    <livewire:admin.user.detail-modal  />
    <x-header title="User Management" icon="o-users" with-anchor separator class="pt-1" >
        <x-slot:middle class="!justify-end">
            <x-input icon="o-bolt" placeholder="Search..." wire:model.live="search" clearable />
        </x-slot:middle>
        <x-slot:actions>
            <x-button icon="o-plus" class="btn-primary" wire:click="$dispatch('openCreate')" />
        </x-slot:actions>
    </x-header>

    <table class="table w-full bg-white rounded-md">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ ucfirst($user->role->name) }}</td>
                    <td>
                        @if (!$user->isRole('accountant'))
                            <x-button icon="o-eye" class="btn-sm btn-ghost" tooltip-left="View Details" wire:click="$dispatch('detailModal', { user:'{{ $user->id }}'})" />

                        @endif
                        <x-button icon="o-pencil" class="btn-sm btn-ghost" tooltip-left="Edit User" />
                        <x-button icon="o-trash" class="btn-sm btn-ghost text-error" tooltip-left="Delete User" wire:confirm wire:click="deleteUser('{{ $user->id }}')"/>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <livewire:admin.user.create />
</div>
