<?php

use Livewire\Volt\Component;
use App\Models\{Candidate, TestInvitation};
use App\Jobs\SendTestInvitationJob;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public bool $showModal = false;

    // Form fields
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $position_applied = '';
    public string $notes = '';
    public string $status = 'invited';

    public function with(): array
    {
        $query = Candidate::query()
            ->with(['createdBy', 'latestTypingTest', 'activeInvitation'])
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

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
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

        $this->closeModal();
        $this->dispatch('candidate-saved');
    }

    public function sendInvitation(int $candidateId): void
    {
        $candidate = Candidate::findOrFail($candidateId);

        // Create invitation
        $invitation = TestInvitation::createForCandidate($candidate);

        // Send email
        SendTestInvitationJob::dispatch($invitation);

        $candidate->update([
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        session()->flash('success', 'Invitation sent to ' . $candidate->name);
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
            <h1 class="text-3xl font-bold text-gray-900">Candidate Management</h1>
            <button
                wire:click="openModal"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg"
            >
                Add Candidate
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, email, or position..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select
                        wire:model.live="statusFilter"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="all">All Statuses</option>
                        <option value="invited">Invited</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="hired">Hired</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Candidates Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Best WPM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tests</th>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $candidate->bestWpm ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $candidate->typing_tests_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button
                                    wire:click="openModal({{ $candidate->id }})"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    Edit
                                </button>
                                @if (!$candidate->activeInvitation)
                                    <button
                                        wire:click="sendInvitation({{ $candidate->id }})"
                                        class="text-green-600 hover:text-green-900"
                                    >
                                        Send Test
                                    </button>
                                @endif
                                <button
                                    wire:click="deleteCandidate({{ $candidate->id }})"
                                    wire:confirm="Are you sure you want to delete this candidate?"
                                    class="text-red-600 hover:text-red-900"
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
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showModal') }">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <div class="relative bg-white rounded-lg max-w-2xl w-full p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">
                        {{ $editingId ? 'Edit Candidate' : 'Add Candidate' }}
                    </h2>

                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                                <input
                                    type="text"
                                    wire:model="name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                >
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input
                                    type="email"
                                    wire:model="email"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                >
                                @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input
                                    type="text"
                                    wire:model="phone"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                >
                                @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Position Applied *</label>
                                <input
                                    type="text"
                                    wire:model="position_applied"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                >
                                @error('position_applied') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select
                                wire:model="status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="invited">Invited</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="hired">Hired</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea
                                wire:model="notes"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            ></textarea>
                            @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                {{ $editingId ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
