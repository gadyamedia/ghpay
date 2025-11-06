<?php

namespace App\Livewire\Admin\User;

use App\Models\Role;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

#[On('refreshUserTable')]
class Users extends Component
{
    use Toast;

    public string $search = '';

    public string $filter = 'all';

    public ?User $user = null;

    private function getUsers()
    {
        $roleIds = Role::all()->pluck('id');

        return User::query()
            ->whereIn('role_id', $roleIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->get();
    }

    public function deleteUser(string $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->delete();

            $this->success('User deleted successfully.');
        } else {
            $this->error('User not found.');
        }
    }

    public function render()
    {
        return view('livewire.admin.user.users', [
            'users' => $this->getUsers(),
        ]);
    }
}
