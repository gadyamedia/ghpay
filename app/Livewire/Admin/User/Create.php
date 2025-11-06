<?php

namespace App\Livewire\Admin\User;

use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;
use Yajra\Address\Entities\Barangay;
use Yajra\Address\Entities\City;
use Yajra\Address\Entities\Province;
use Yajra\Address\Entities\Region;

class Create extends Component
{
    use Toast;

    #[Validate('required|string')]
    public $name;

    #[Validate('required|email')]
    public $email;

    #[Validate('required|exists:roles,id')]
    public $role;

    #[Validate('required|string|min:8')]
    public $password;

    #[Validate('boolean')]
    public bool $sendInvitation = true;

    public $createmodal = false;

    public function generatePassword()
    {
        $this->password = Str::password(12);
    }

    #[On('openCreate')]
    public function open()
    {
        $this->createmodal = true;
        $this->step = 1;
    }

    #[Validate('nullable|exists:regions,region_id')]
    public $region;

    /**
     * MaryUI <x-choices> option lists
     */
    public array $regionsOptions = [];

    public array $provinceOptions = [];

    public array $cityOptions = [];

    public array $barangayOptions = [];

    #[Validate('nullable|exists:provinces,province_id')]
    public $province;

    #[Validate('nullable|exists:cities,city_id')]
    public $city;

    #[Validate('nullable|exists:barangays,code')]
    public $barangay;

    #[Validate('nullable|string')]
    public $street;

    public int $step = 1;

    // Employment Details

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

    #[Validate('nullable|string')]
    public $position;

    #[Validate('nullable|string')]
    public $gender;

    #[Validate('nullable|string')]
    public $civil_status;

    #[Validate('nullable|string')]
    public $nationality;

    #[Validate('nullable|date')]
    public $hire_date;

    #[Validate('nullable|date')]
    public $birthday;

    /**
     * Initialise empty option sets
     */
    public function mount(): void
    {
        $this->regionsOptions = [];
        $this->provinceOptions = [];
        $this->cityOptions = [];
        $this->barangayOptions = [];

        $this->searchRegions();
    }

    public function create()
    {
        $this->validate();

        if (! $this->isAccountant()) {
            $this->validate($this->employeeDetailRules());
        }

        $plainPassword = $this->password;

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role,
            'password' => Hash::make($plainPassword),
        ]);

        if (! $this->isAccountant()) {
            UserDetail::create([
                'user_id' => $user->id,
                'hourly_rate' => $this->hourly_rate,
                'position' => $this->position,
                'gender' => $this->gender,
                'civil_status' => $this->civil_status,
                'nationality' => $this->nationality,
                'hire_date' => $this->hire_date,
                'birthday' => $this->birthday,
                'pagibig' => $this->pagibig_number,
                'sss' => $this->sss_number,
                'tin' => $this->tin_number,
                'philhealth' => $this->philhealth_number,
                'street' => $this->street,
                'barangay_id' => $this->barangay,
                'city_id' => $this->city,
                'province_id' => $this->province,
                'region_id' => $this->region,
            ]);
        }

        if ($this->sendInvitation) {
            Mail::to($user->email)->send(new UserInvitationMail($user, $plainPassword));
        }

        $message = $this->sendInvitation
            ? 'User created and invitation email sent.'
            : 'User created successfully.';

        $this->success($message);

        $this->dispatch('refreshUserTable');

        $this->createmodal = false;

        $this->reset();

        $this->searchRegions();
    }

    public function next(): void
    {
        if ($this->step < $this->maxStep) {
            $this->step++;
        }
    }

    public function prev(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function updatedRole($roleId): void
    {
        if ($this->isAccountant()) {
            $this->step = 1;
        } else {
            $this->step = min($this->step, $this->maxStep);
        }
    }

    protected function employeeDetailRules(): array
    {
        return [
            'street' => ['required', 'string'],
            'region' => ['required', 'exists:regions,region_id'],
            'province' => ['required', 'exists:provinces,province_id'],
            'city' => ['required', 'exists:cities,city_id'],
            'barangay' => ['required', 'exists:barangays,code'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'position' => ['required', 'string'],
            'gender' => ['required', 'string'],
            'civil_status' => ['required', 'string'],
            'nationality' => ['required', 'string'],
            'hire_date' => ['required', 'date'],
            'birthday' => ['required', 'date'],
            'pagibig_number' => ['required', 'string'],
            'sss_number' => ['required', 'string'],
            'tin_number' => ['required', 'string'],
            'philhealth_number' => ['required', 'string'],
        ];
    }

    public function updatedRegion($regionId)
    {
        $this->province = null;
        $this->city = null;
        $this->barangay = null;

        $this->provinceOptions = [];
        $this->cityOptions = [];
        $this->barangayOptions = [];

        if ($regionId) {
            $this->searchProvinces('', $regionId);
        }
    }

    public function updatedProvince($provinceId)
    {
        $this->city = null;
        $this->barangay = null;

        $this->cityOptions = [];
        $this->barangayOptions = [];

        if ($provinceId) {
            $this->searchCities('', $provinceId);
        }
    }

    public function updatedCity($cityId)
    {
        $this->barangay = null;
        $this->barangayOptions = [];

        if ($cityId) {
            $this->searchBarangays('', $cityId);
        }
    }

    public function getMaxStepProperty(): int
    {
        return $this->isAccountant() ? 1 : 3;
    }

    public function searchRegions(string $value = ''): void
    {
        $selected = $this->region
            ? Region::where('region_id', $this->region)->get()
            : collect();

        $matches = Region::query()
            ->when($value, fn ($query) => $query->where('name', 'like', "%$value%"))
            ->orderBy('name')
            ->limit(15)
            ->get();

        $this->regionsOptions = $this->formatOptions($matches->merge($selected), 'region_id');
    }

    public function searchProvinces(string $value = '', ?string $regionId = null): void
    {
        $regionId = $regionId ?: $this->region;

        if (! $regionId) {
            $this->provinceOptions = [];

            return;
        }

        $selected = $this->province
            ? Province::where('province_id', $this->province)->get()
            : collect();

        $matches = Province::query()
            ->where('region_id', $regionId)
            ->when($value, fn ($query) => $query->where('name', 'like', "%$value%"))
            ->orderBy('name')
            ->limit(15)
            ->get();

        $this->provinceOptions = $this->formatOptions($matches->merge($selected), 'province_id');
    }

    public function searchCities(string $value = '', ?string $provinceId = null): void
    {
        $provinceId = $provinceId ?: $this->province;

        if (! $provinceId) {
            $this->cityOptions = [];

            return;
        }

        $selected = $this->city
            ? City::where('city_id', $this->city)->get()
            : collect();

        $matches = City::query()
            ->where('province_id', $provinceId)
            ->when($value, fn ($query) => $query->where('name', 'like', "%$value%"))
            ->orderBy('name')
            ->limit(15)
            ->get();

        $this->cityOptions = $this->formatOptions($matches->merge($selected), 'city_id');
    }

    public function searchBarangays(string $value = '', ?string $cityId = null): void
    {
        $cityId = $cityId ?: $this->city;

        if (! $cityId) {
            $this->barangayOptions = [];

            return;
        }

        $selected = $this->barangay
            ? Barangay::where('code', $this->barangay)->get()
            : collect();

        $matches = Barangay::query()
            ->where('city_id', $cityId)
            ->when($value, fn ($query) => $query->where('name', 'like', "%$value%"))
            ->orderBy('name')
            ->limit(15)
            ->get();

        $this->barangayOptions = $this->formatOptions($matches->merge($selected), 'code');
    }

    private function formatOptions(Collection $items, string $key): array
    {
        return $items
            ->unique($key)
            ->map(fn ($item) => [
                'id' => (string) $item->{$key},
                'name' => $item->name,
            ])
            ->values()
            ->toArray();
    }

    private function isAccountant(): bool
    {
        return (string) $this->role === '4';
    }

    public function render()
    {
        return view('livewire.admin.user.create', [
            'roles' => \App\Models\Role::all(),
            'maxStep' => $this->maxStep,
            'genders' => [
                ['id' => 'Male', 'name' => 'Male'],
                ['id' => 'Female', 'name' => 'Female'],
                ['id' => 'Other', 'name' => 'Other'],
            ],
        ]);
    }
}
