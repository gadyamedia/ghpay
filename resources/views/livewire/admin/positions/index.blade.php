<?php

use App\Models\Position;
use App\Models\TypingTextSample;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public string $title = '';

    public string $department = '';

    public string $employment_type = 'full-time';

    public string $location_type = 'onsite';

    public string $location = '';

    public string $description = '';

    public string $requirements = '';

    public string $responsibilities = '';

    public string $benefits = '';

    public ?int $salary_min = null;

    public ?int $salary_max = null;

    public bool $show_salary = false;

    public string $status = 'draft';

    public ?string $application_deadline = null;

    public bool $require_typing_test = false;

    public bool $auto_send_typing_test = false;

    public ?int $minimum_wpm = null;

    public ?int $typing_text_sample_id = null;

    public bool $notify_admin_on_application = true;

    public string $notification_email = '';

    // Form settings
    public bool $show_cover_letter = true;

    public bool $require_cover_letter = false;

    public bool $show_portfolio_url = false;

    public bool $require_portfolio_url = false;

    public bool $show_linkedin_url = true;

    public bool $require_linkedin_url = false;

    public bool $show_github_url = false;

    public bool $require_github_url = false;

    public bool $show_location = true;

    public bool $require_location = false;

    // Filters
    public string $filterStatus = '';

    public string $filterDepartment = '';

    public string $searchTerm = '';

    public function with(): array
    {
        $query = Position::query()
            ->with(['createdBy', 'typingTextSample'])
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterDepartment, fn ($q) => $q->where('department', $this->filterDepartment))
            ->when($this->searchTerm, fn ($q) => $q->search($this->searchTerm))
            ->latest();

        return [
            'positions' => $query->paginate(10),
            'typingSamples' => TypingTextSample::where('is_active', true)->get(),
            'departments' => Position::distinct()->pluck('department')->filter()->sort()->values(),
        ];
    }

    public function openModal(?int $positionId = null): void
    {
        if ($positionId) {
            $position = Position::findOrFail($positionId);
            $this->editingId = $position->id;
            $this->title = $position->title;
            $this->department = $position->department ?? '';
            $this->employment_type = $position->employment_type;
            $this->location_type = $position->location_type;
            $this->location = $position->location ?? '';
            $this->description = $position->description;
            $this->requirements = $position->requirements ?? '';
            $this->responsibilities = $position->responsibilities ?? '';
            $this->benefits = $position->benefits ?? '';
            $this->salary_min = $position->salary_min;
            $this->salary_max = $position->salary_max;
            $this->show_salary = $position->show_salary;
            $this->status = $position->status;
            $this->application_deadline = $position->application_deadline?->format('Y-m-d');
            $this->require_typing_test = $position->require_typing_test;
            $this->auto_send_typing_test = $position->auto_send_typing_test;
            $this->minimum_wpm = $position->minimum_wpm;
            $this->typing_text_sample_id = $position->typing_text_sample_id;
            $this->notify_admin_on_application = $position->notify_admin_on_application;
            $this->notification_email = $position->notification_email ?? '';

            // Load form settings
            $formSettings = $position->form_settings;
            $this->show_cover_letter = $formSettings['show_cover_letter'] ?? true;
            $this->require_cover_letter = $formSettings['require_cover_letter'] ?? false;
            $this->show_portfolio_url = $formSettings['show_portfolio_url'] ?? false;
            $this->require_portfolio_url = $formSettings['require_portfolio_url'] ?? false;
            $this->show_linkedin_url = $formSettings['show_linkedin_url'] ?? true;
            $this->require_linkedin_url = $formSettings['require_linkedin_url'] ?? false;
            $this->show_github_url = $formSettings['show_github_url'] ?? false;
            $this->require_github_url = $formSettings['require_github_url'] ?? false;
            $this->show_location = $formSettings['show_location'] ?? true;
            $this->require_location = $formSettings['require_location'] ?? false;
        } else {
            $this->resetForm();
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'required|in:full-time,part-time,contract,internship',
            'location_type' => 'required|in:remote,hybrid,onsite',
            'location' => 'nullable|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'benefits' => 'nullable|string',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0|gte:salary_min',
            'show_salary' => 'boolean',
            'status' => 'required|in:draft,open,closed',
            'application_deadline' => 'nullable|date|after:today',
            'require_typing_test' => 'boolean',
            'auto_send_typing_test' => 'boolean',
            'minimum_wpm' => 'nullable|integer|min:0|max:200',
            'typing_text_sample_id' => 'nullable|exists:typing_text_samples,id',
            'notify_admin_on_application' => 'boolean',
            'notification_email' => 'nullable|email',
        ]);

        // Add form settings
        $validated['form_settings'] = [
            'show_cover_letter' => $this->show_cover_letter,
            'require_cover_letter' => $this->require_cover_letter,
            'show_portfolio_url' => $this->show_portfolio_url,
            'require_portfolio_url' => $this->require_portfolio_url,
            'show_linkedin_url' => $this->show_linkedin_url,
            'require_linkedin_url' => $this->require_linkedin_url,
            'show_github_url' => $this->show_github_url,
            'require_github_url' => $this->require_github_url,
            'show_location' => $this->show_location,
            'require_location' => $this->require_location,
        ];

        if ($this->editingId) {
            $position = Position::findOrFail($this->editingId);
            $position->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            Position::create($validated);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('position-saved');
    }

    public function deletePosition(int $positionId): void
    {
        Position::findOrFail($positionId)->delete();
        $this->dispatch('position-deleted');
    }

    public function duplicatePosition(int $positionId): void
    {
        $original = Position::findOrFail($positionId);
        $new = $original->replicate();
        $new->title = $original->title.' (Copy)';
        $new->status = 'draft';
        $new->views_count = 0;
        $new->applications_count = 0;
        $new->created_by = auth()->id();
        $new->save();

        $this->dispatch('position-duplicated');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->department = '';
        $this->employment_type = 'full-time';
        $this->location_type = 'onsite';
        $this->location = '';
        $this->description = '';
        $this->requirements = '';
        $this->responsibilities = '';
        $this->benefits = '';
        $this->salary_min = null;
        $this->salary_max = null;
        $this->show_salary = false;
        $this->status = 'draft';
        $this->application_deadline = null;
        $this->require_typing_test = false;
        $this->auto_send_typing_test = false;
        $this->minimum_wpm = null;
        $this->typing_text_sample_id = null;
        $this->notify_admin_on_application = true;
        $this->notification_email = '';

        // Reset form settings
        $this->show_cover_letter = true;
        $this->require_cover_letter = false;
        $this->show_portfolio_url = false;
        $this->require_portfolio_url = false;
        $this->show_linkedin_url = true;
        $this->require_linkedin_url = false;
        $this->show_github_url = false;
        $this->require_github_url = false;
        $this->show_location = true;
        $this->require_location = false;

        $this->resetValidation();
    }

    #[On('position-saved')]
    #[On('position-deleted')]
    #[On('position-duplicated')]
    public function refreshList(): void
    {
        // Triggers re-render
    }
}; ?>

<div>
    <x-header title="Job Positions" subtitle="Manage open positions and job postings" icon="o-briefcase" separator>
        <x-slot:actions>
            <x-button label="Add Position" icon="o-plus" class="btn-primary" wire:click="openModal" />
        </x-slot:actions>
    </x-header>

    <!-- Filters -->
    <x-card class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input
                label="Search"
                wire:model.live.debounce.300ms="searchTerm"
                icon="o-magnifying-glass"
                placeholder="Search positions..."
            />

            <x-select
                label="Status"
                wire:model.live="filterStatus"
                icon="o-check-circle"
                :options="[
                    ['id' => '', 'name' => 'All Statuses'],
                    ['id' => 'draft', 'name' => 'Draft'],
                    ['id' => 'open', 'name' => 'Open'],
                    ['id' => 'closed', 'name' => 'Closed'],
                ]"
            />

            <x-select
                label="Department"
                wire:model.live="filterDepartment"
                icon="o-building-office"
                :options="[['id' => '', 'name' => 'All Departments'], ...$departments->map(fn($d) => ['id' => $d, 'name' => $d])->toArray()]"
            />
        </div>
    </x-card>

    <!-- Positions List -->
    <div class="space-y-4 mt-6">
        @forelse ($positions as $position)
            <x-card wire:key="position-{{ $position->id }}">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-start gap-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg">{{ $position->title }}</h3>
                                @if ($position->department)
                                    <p class="text-sm text-gray-600 mt-1">{{ $position->department }}</p>
                                @endif

                                <div class="flex flex-wrap gap-2 mt-3">
                                    <x-badge
                                        :value="ucfirst($position->status)"
                                        class="{{ match($position->status) {
                                            'open' => 'badge-success',
                                            'draft' => 'badge-warning',
                                            'closed' => 'badge-error',
                                        } }}"
                                    />
                                    <x-badge
                                        :value="str_replace('-', ' ', ucfirst($position->employment_type))"
                                        class="badge-primary"
                                    />
                                    <x-badge
                                        :value="ucfirst($position->location_type)"
                                        class="badge-info"
                                    />
                                    @if ($position->location)
                                        <x-badge :value="$position->location" class="badge-ghost" />
                                    @endif
                                    @if ($position->require_typing_test)
                                        <x-badge value="Typing Test Required" class="badge-accent" icon="o-pencil-square" />
                                    @endif
                                </div>

                                <div class="flex items-center gap-4 mt-3 text-sm text-gray-600">
                                    <span class="flex items-center gap-1">
                                        <x-icon name="o-eye" class="w-4 h-4" />
                                        {{ $position->views_count }} views
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <x-icon name="o-document-text" class="w-4 h-4" />
                                        {{ $position->applications_count }} applications
                                    </span>
                                    @if ($position->application_deadline)
                                        <span class="flex items-center gap-1">
                                            <x-icon name="o-calendar" class="w-4 h-4" />
                                            Deadline: {{ $position->application_deadline->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 ml-4">
                        <x-button
                            label="Edit"
                            icon="o-pencil"
                            wire:click="openModal({{ $position->id }})"
                            class="btn-sm"
                        />
                        <x-button
                            label="Duplicate"
                            icon="o-document-duplicate"
                            wire:click="duplicatePosition({{ $position->id }})"
                            class="btn-sm btn-ghost"
                        />
                        <x-button
                            icon="o-trash"
                            wire:click="deletePosition({{ $position->id }})"
                            wire:confirm="Are you sure you want to delete this position?"
                            class="btn-sm btn-error"
                        />
                    </div>
                </div>
            </x-card>
        @empty
            <x-card>
                <div class="text-center py-12">
                    <x-icon name="o-briefcase" class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                    <h3 class="text-lg font-semibold mb-2">No positions yet</h3>
                    <p class="text-gray-600 mb-4">Create your first job position to get started.</p>
                    <x-button label="Add Position" icon="o-plus" class="btn-primary" wire:click="openModal" />
                </div>
            </x-card>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $positions->links() }}
    </div>

    <!-- Modal -->
    <x-modal wire:model="showModal" :title="$editingId ? 'Edit Position' : 'Add Position'" subtitle="Job position details">
        <x-form wire:submit="save">
            <!-- Basic Information -->
            <div class="space-y-4">
                <h4 class="font-semibold text-lg">Basic Information</h4>

                <x-input
                    label="Job Title"
                    wire:model="title"
                    icon="o-briefcase"
                    placeholder="e.g., Senior Software Engineer"
                />

                <x-input
                    label="Department"
                    wire:model="department"
                    icon="o-building-office"
                    placeholder="e.g., Engineering"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-select
                        label="Employment Type"
                        wire:model="employment_type"
                        icon="o-clock"
                        :options="[
                            ['id' => 'full-time', 'name' => 'Full-time'],
                            ['id' => 'part-time', 'name' => 'Part-time'],
                            ['id' => 'contract', 'name' => 'Contract'],
                            ['id' => 'internship', 'name' => 'Internship'],
                        ]"
                    />

                    <x-select
                        label="Location Type"
                        wire:model="location_type"
                        icon="o-map-pin"
                        :options="[
                            ['id' => 'remote', 'name' => 'Remote'],
                            ['id' => 'hybrid', 'name' => 'Hybrid'],
                            ['id' => 'onsite', 'name' => 'Onsite'],
                        ]"
                    />
                </div>

                <x-input
                    label="Location"
                    wire:model="location"
                    icon="o-map"
                    placeholder="e.g., San Francisco, CA"
                    hint="City, State or 'Remote' if fully remote"
                />

                <x-textarea
                    label="Description"
                    wire:model="description"
                    placeholder="Describe the role and what the candidate will be doing..."
                    rows="4"
                />

                <x-textarea
                    label="Requirements"
                    wire:model="requirements"
                    placeholder="List the qualifications and requirements..."
                    rows="4"
                    hint="Optional"
                />

                <x-textarea
                    label="Responsibilities"
                    wire:model="responsibilities"
                    placeholder="List the key responsibilities..."
                    rows="4"
                    hint="Optional"
                />

                <x-textarea
                    label="Benefits"
                    wire:model="benefits"
                    placeholder="List the benefits and perks..."
                    rows="3"
                    hint="Optional"
                />
            </div>

            <!-- Salary Information -->
            <div class="space-y-4 mt-6">
                <h4 class="font-semibold text-lg">Salary Information</h4>

                <div class="grid grid-cols-2 gap-4">
                    <x-input
                        label="Minimum Salary (₱)"
                        wire:model="salary_min"
                        type="number"
                        icon="o-currency-dollar"
                        placeholder="25000"
                        hint="Monthly salary in Philippine Peso"
                    />

                    <x-input
                        label="Maximum Salary (₱)"
                        wire:model="salary_max"
                        type="number"
                        icon="o-currency-dollar"
                        placeholder="40000"
                        hint="Monthly salary in Philippine Peso"
                    />
                </div>

                <x-checkbox
                    label="Show salary range on job posting"
                    wire:model="show_salary"
                />
            </div>

            <!-- Status & Deadline -->
            <div class="space-y-4 mt-6">
                <h4 class="font-semibold text-lg">Status & Deadline</h4>

                <div class="grid grid-cols-2 gap-4">
                    <x-select
                        label="Status"
                        wire:model="status"
                        icon="o-check-circle"
                        :options="[
                            ['id' => 'draft', 'name' => 'Draft'],
                            ['id' => 'open', 'name' => 'Open'],
                            ['id' => 'closed', 'name' => 'Closed'],
                        ]"
                        hint="Only 'Open' positions appear on careers page"
                    />

                    <x-input
                        label="Application Deadline"
                        wire:model="application_deadline"
                        type="date"
                        icon="o-calendar"
                        hint="Optional"
                    />
                </div>
            </div>

            <!-- Typing Test Settings -->
            <div class="space-y-4 mt-6">
                <h4 class="font-semibold text-lg">Typing Test Settings</h4>

                <x-checkbox
                    label="Require typing test for this position"
                    wire:model.live="require_typing_test"
                />

                @if ($require_typing_test)
                    <div class="ml-6 space-y-4">
                        <x-checkbox
                            label="Automatically send typing test invitation after application"
                            wire:model="auto_send_typing_test"
                        />

                        <x-input
                            label="Minimum WPM Required"
                            wire:model="minimum_wpm"
                            type="number"
                            icon="o-arrow-trending-up"
                            placeholder="40"
                            hint="Candidates below this WPM will be filtered"
                        />

                        <x-select
                            label="Typing Test Sample"
                            wire:model="typing_text_sample_id"
                            icon="o-document-text"
                            :options="[['id' => '', 'name' => 'Use random sample'], ...$typingSamples->map(fn($s) => ['id' => $s->id, 'name' => $s->title])->toArray()]"
                            hint="Optional - leave blank to use random sample"
                        />
                    </div>
                @endif
            </div>

            <!-- Notification Settings -->
            <div class="space-y-4 mt-6">
                <h4 class="font-semibold text-lg">Notification Settings</h4>

                <x-checkbox
                    label="Send email notification when application is received"
                    wire:model.live="notify_admin_on_application"
                />

                @if ($notify_admin_on_application)
                    <div class="ml-6">
                        <x-input
                            label="Notification Email"
                            wire:model="notification_email"
                            type="email"
                            icon="o-envelope"
                            placeholder="hiring@company.com"
                            hint="Leave blank to use your email"
                        />
                    </div>
                @endif
            </div>

            <!-- Application Form Customization -->
            <div class="space-y-4 mt-6">
                <h4 class="font-semibold text-lg">Application Form Fields</h4>
                <p class="text-sm text-gray-600">Customize which fields appear on the application form</p>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Cover Letter -->
                    <div class="space-y-2">
                        <x-checkbox
                            label="Show Cover Letter field"
                            wire:model.live="show_cover_letter"
                        />
                        @if($show_cover_letter)
                            <div class="ml-6">
                                <x-checkbox
                                    label="Make it required"
                                    wire:model="require_cover_letter"
                                />
                            </div>
                        @endif
                    </div>

                    <!-- Location -->
                    <div class="space-y-2">
                        <x-checkbox
                            label="Show Location field"
                            wire:model.live="show_location"
                        />
                        @if($show_location)
                            <div class="ml-6">
                                <x-checkbox
                                    label="Make it required"
                                    wire:model="require_location"
                                />
                            </div>
                        @endif
                    </div>

                    <!-- Portfolio URL -->
                    <div class="space-y-2">
                        <x-checkbox
                            label="Show Portfolio URL field"
                            wire:model.live="show_portfolio_url"
                        />
                        @if($show_portfolio_url)
                            <div class="ml-6">
                                <x-checkbox
                                    label="Make it required"
                                    wire:model="require_portfolio_url"
                                />
                            </div>
                        @endif
                    </div>

                    <!-- LinkedIn URL -->
                    <div class="space-y-2">
                        <x-checkbox
                            label="Show LinkedIn URL field"
                            wire:model.live="show_linkedin_url"
                        />
                        @if($show_linkedin_url)
                            <div class="ml-6">
                                <x-checkbox
                                    label="Make it required"
                                    wire:model="require_linkedin_url"
                                />
                            </div>
                        @endif
                    </div>

                    <!-- GitHub URL -->
                    <div class="space-y-2">
                        <x-checkbox
                            label="Show GitHub URL field"
                            wire:model.live="show_github_url"
                        />
                        @if($show_github_url)
                            <div class="ml-6">
                                <x-checkbox
                                    label="Make it required"
                                    wire:model="require_github_url"
                                />
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showModal = false" />
                <x-button label="{{ $editingId ? 'Update' : 'Create' }}" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
