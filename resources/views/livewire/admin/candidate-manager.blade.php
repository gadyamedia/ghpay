<?php

use Livewire\Volt\Component;
use App\Models\{Candidate, TestInvitation, TypingTextSample};
use App\Jobs\SendTestInvitationJob;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public bool $showModal = false;
    public bool $showTestModal = false;

    // Form fields
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $position_applied = '';
    public string $notes = '';
    public string $status = 'invited';

    // Test invitation fields
    public ?int $sendingToId = null;
    public ?int $selectedTestSampleId = null;

    public function with(): array
    {
        $query = Candidate::query()
            ->with(['createdBy', 'latestTypingTest.typingTextSample', 'activeInvitation'])
            ->withCount('typingTests');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('position_applied', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return [
            'candidates' => $query->latest()->paginate(15),
            'testSamples' => TypingTextSample::where('is_active', true)->orderBy('difficulty')->get(),
        ];
    }

    public function openModal(?int $candidateId = null): void
    {
        if ($candidateId) {
            $candidate = Candidate::findOrFail($candidateId);
            $this->editingId = $candidate->id;
            $this->name = $candidate->name;
            $this->email = $candidate->email;
            $this->phone = $candidate->phone ?? '';
            $this->position_applied = $candidate->position_applied;
            $this->notes = $candidate->notes ?? '';
            $this->status = $candidate->status;
        } else {
            $this->resetForm();
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:candidates,email,' . ($this->editingId ?? 'NULL'),
            'phone' => 'nullable|string|max:20',
            'position_applied' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:invited,in_progress,completed,hired,rejected',
        ]);

        if ($this->editingId) {
            $candidate = Candidate::findOrFail($this->editingId);
            $candidate->update($validated);
        } else {
            $validated['created_by'] = auth()->id();
            $candidate = Candidate::create($validated);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('candidate-saved');
    }

    public function openTestModal(int $candidateId): void
    {
        $this->sendingToId = $candidateId;
        $this->selectedTestSampleId = null;
        $this->showTestModal = true;
    }

    public function sendInvitation(): void
    {
        $this->validate([
            'selectedTestSampleId' => 'required|exists:typing_text_samples,id',
        ]);

        $candidate = Candidate::findOrFail($this->sendingToId);

        // Mark old invitation as expired if exists
        if ($candidate->activeInvitation) {
            $candidate->activeInvitation->update(['expires_at' => now()]);
        }

        // Create new invitation with specific test sample
        $invitation = TestInvitation::createForCandidate($candidate, $this->selectedTestSampleId);

        // Send email
        SendTestInvitationJob::dispatch($invitation);

        $candidate->update([
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $this->showTestModal = false;
        $this->sendingToId = null;
        $this->selectedTestSampleId = null;

        session()->flash('success', 'Invitation sent to ' . $candidate->name);
    }

    public function resendInvitation(int $candidateId): void
    {
        $this->openTestModal($candidateId);
    }

    public function deleteCandidate(int $candidateId): void
    {
        Candidate::findOrFail($candidateId)->delete();
        $this->dispatch('candidate-deleted');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->position_applied = '';
        $this->notes = '';
        $this->status = 'invited';
        $this->resetValidation();
    }

    #[On('candidate-saved')]
    #[On('candidate-deleted')]
    public function refreshList(): void
    {
        // Triggers re-render
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Candidate Management</h1>
            <x-button
                label="Add Candidate"
                icon="o-plus"
                class="btn-primary"
                wire:click="openModal"
            />
        </div>

        <!-- Filters -->
        <x-card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input
                    label="Search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, email, or position..."
                    icon="o-magnifying-glass"
                    clearable
                />

                <x-select
                    label="Status"
                    wire:model.live="statusFilter"
                    icon="o-funnel"
                    :options="[
                        ['id' => 'all', 'name' => 'All Statuses'],
                        ['id' => 'invited', 'name' => 'Invited'],
                        ['id' => 'in_progress', 'name' => 'In Progress'],
                        ['id' => 'completed', 'name' => 'Completed'],
                        ['id' => 'hired', 'name' => 'Hired'],
                        ['id' => 'rejected', 'name' => 'Rejected'],
                    ]"
                />
            </div>
        </x-card>

        <!-- Candidates Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latest Test</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Best WPM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Tests</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($candidates as $candidate)
                        <tr wire:key="candidate-{{ $candidate->id }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $candidate->name }}</div>
                                <div class="text-sm text-gray-500">{{ $candidate->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $candidate->position_applied }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ match($candidate->status) {
                                        'invited' => 'bg-blue-100 text-blue-800',
                                        'in_progress' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'hired' => 'bg-purple-100 text-purple-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                    } }}
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $candidate->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                @if ($candidate->latestTypingTest)
                                    <div class="font-medium text-gray-900">{{ $candidate->latestTypingTest->typingTextSample?->title ?? 'Custom' }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $candidate->latestTypingTest->completed_at->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-gray-400">No tests yet</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $candidate->bestWpm ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $candidate->typing_tests_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-3">
                                    <a
                                        href="{{ route('admin.candidates.show', $candidate->id) }}"
                                        class="text-indigo-600 hover:text-indigo-900 hover:underline"
                                    >
                                        View
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="openModal({{ $candidate->id }})"
                                        class="text-blue-600 hover:text-blue-900 hover:underline cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                    @if ($candidate->activeInvitation)
                                        <button
                                            type="button"
                                            wire:click="openTestModal({{ $candidate->id }})"
                                            class="text-orange-600 hover:text-orange-900 hover:underline cursor-pointer"
                                            title="Send a new invitation link"
                                        >
                                            Resend Test
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="openTestModal({{ $candidate->id }})"
                                            class="text-green-600 hover:text-green-900 hover:underline cursor-pointer"
                                        >
                                            Send Test
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        wire:click="deleteCandidate({{ $candidate->id }})"
                                        wire:confirm="Are you sure you want to delete this candidate?"
                                        class="text-red-600 hover:text-red-900 hover:underline cursor-pointer"
                                    >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No candidates found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $candidates->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <x-modal wire:model="showModal" :title="$editingId ? 'Edit Candidate' : 'Add Candidate'" subtitle="Manage candidate information">
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input
                    label="Name"
                    wire:model="name"
                    icon="o-user"
                    placeholder="Full name"
                    inline
                />

                <x-input
                    label="Email"
                    wire:model="email"
                    type="email"
                    icon="o-envelope"
                    placeholder="email@example.com"
                    inline
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input
                    label="Phone"
                    wire:model="phone"
                    icon="o-phone"
                    placeholder="Phone number"
                    inline
                />

                <x-input
                    label="Position Applied"
                    wire:model="position_applied"
                    icon="o-briefcase"
                    placeholder="Position"
                    inline
                />
            </div>

            <x-select
                label="Status"
                wire:model="status"
                icon="o-flag"
                :options="[
                    ['id' => 'invited', 'name' => 'Invited'],
                    ['id' => 'in_progress', 'name' => 'In Progress'],
                    ['id' => 'completed', 'name' => 'Completed'],
                    ['id' => 'hired', 'name' => 'Hired'],
                    ['id' => 'rejected', 'name' => 'Rejected'],
                ]"
                inline
            />

            <x-textarea
                label="Notes"
                wire:model="notes"
                placeholder="Additional notes about the candidate..."
                rows="3"
                inline
            />

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showModal = false" />
                <x-button label="{{ $editingId ? 'Update' : 'Create' }}" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Send Test Modal -->
    <x-modal wire:model="showTestModal" title="Send Typing Test" subtitle="Select a test sample to send" persistent>
        <x-form wire:submit="sendInvitation">
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Choose which typing test sample to send to the candidate. Each test has different difficulty levels and content.
                </p>

                @if ($testSamples->isEmpty())
                    <x-alert icon="o-exclamation-triangle" class="alert-warning">
                        No active test samples available. Please create test samples first.
                    </x-alert>
                @else
                    <div class="space-y-3">
                        @foreach ($testSamples as $sample)
                            <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition
                                {{ $selectedTestSampleId === $sample->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                                <input
                                    type="radio"
                                    wire:model.live="selectedTestSampleId"
                                    value="{{ $sample->id }}"
                                    class="mt-1 mr-3"
                                >
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $sample->title }}</h4>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full capitalize
                                            {{ match($sample->difficulty) {
                                                'easy' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                'hard' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            } }}">
                                            {{ $sample->difficulty }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $sample->word_count }} words</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 line-clamp-2">{{ Str::limit($sample->content, 100) }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @error('selectedTestSampleId')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                @endif

                <x-alert icon="o-shield-check" class="alert-info">
                    <strong>Anti-Cheat Measures:</strong> Copy/paste disabled, browser spellcheck disabled, test auto-submits when complete.
                </x-alert>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showTestModal = false" />
                <x-button
                    label="Send Test Invitation"
                    class="btn-primary"
                    type="submit"
                    spinner="sendInvitation"
                    :disabled="!$selectedTestSampleId"
                />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
