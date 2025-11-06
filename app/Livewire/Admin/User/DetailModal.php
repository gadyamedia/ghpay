<?php

namespace App\Livewire\Admin\User;

use App\Models\User;
use App\Models\UserDetail;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class DetailModal extends Component
{
    use Toast;

    public bool $userDetailModal = false;

    public ?User $user = null;

    // Fields

    #[Validate('nullable|string')]
    public $sss_number;

    #[Validate('nullable|string')]
    public $tin_number;

    #[Validate('nullable|string')]
    public $philhealth_number;

    #[Validate('nullable|string')]
    public $pagibig_number;

    #[Validate('nullable|numeric')]
    public $hourly_rate;

    #[Validate('required|string')]
    public $position;

    #[Validate('required|string')]
    public $gender;

    #[Validate('required|string')]
    public $civil_status;

    #[Validate('required|string')]
    public $nationality = 'Filipino';

    #[Validate('nullable|date')]
    public $hire_date;

    #[Validate('nullable|date')]
    public $birthday;

    #[On('detailModal')]
    public function openDetailModal(string $user): void
    {
        $this->user = User::with('detail')->find($user);

        if (! $this->user) {
            $this->resetDetailFields();

            return;
        }

        $this->userDetailModal = true;

        $detail = $this->user->detail;

        if (! $detail) {
            $this->resetDetailFields();

            return;
        }

        $this->sss_number = $detail->sss;
        $this->tin_number = $detail->tin;
        $this->philhealth_number = $detail->philhealth;
        $this->pagibig_number = $detail->pagibig;
        $this->hourly_rate = $detail->hourly_rate;
        $this->position = $detail->position;
        $this->gender = $detail->gender;
        $this->civil_status = $detail->civil_status;
        $this->nationality = $detail->nationality ?? 'Filipino';
        $this->hire_date = optional($detail->hire_date)?->format('Y-m-d');
        $this->birthday = optional($detail->birthday)?->format('Y-m-d');
    }

    public function updateOrCreateDetail(): void
    {
        $this->validate();

        if (! $this->user) {
            return;
        }

        UserDetail::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'sss' => $this->sss_number,
                'tin' => $this->tin_number,
                'philhealth' => $this->philhealth_number,
                'pagibig' => $this->pagibig_number,
                'hourly_rate' => $this->hourly_rate,
                'position' => $this->position,
                'gender' => $this->gender,
                'civil_status' => $this->civil_status,
                'nationality' => $this->nationality,
                'hire_date' => $this->hire_date,
                'birthday' => $this->birthday,
            ]
        );

        $this->userDetailModal = false;

        $this->resetDetailFields();

        $this->success('User details updated successfully.');

        $this->dispatch('refreshUserTable');
    }

    public function closeModal(): void
    {
        $this->userDetailModal = false;
        $this->resetDetailFields();
    }

    protected function resetDetailFields(): void
    {
        $this->sss_number = null;
        $this->tin_number = null;
        $this->philhealth_number = null;
        $this->pagibig_number = null;
        $this->hourly_rate = null;
        $this->position = null;
        $this->gender = null;
        $this->civil_status = null;
        $this->nationality = 'Filipino';
        $this->hire_date = null;
        $this->birthday = null;
    }

    public function render()
    {
        return view('livewire.admin.user.detail-modal', [
            'genders' => [
                ['id' => 'Male', 'name' => 'Male'],
                ['id' => 'Female', 'name' => 'Female'],
                ['id' => 'Other', 'name' => 'Other'],
            ],

        ]);
    }
}
