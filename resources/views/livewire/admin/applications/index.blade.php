<?php

use App\Mail\ApplicationRejected;
use App\Mail\ApplicationStatusUpdated;
use App\Models\Application;
use App\Models\Position;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public bool $showDetailModal = false;

    public ?int $viewingId = null;

    public string $adminNotes = '';

    // Filters
    public string $filterStatus = '';

    public ?int $filterPosition = null;

    public string $searchTerm = '';

    // Bulk actions
    public array $selectedApplications = [];

    public bool $selectAll = false;

    public function with(): array
    {
        $query = Application::query()
            ->with(['position', 'candidate', 'reviewedBy'])
            ->when($this->filterStatus, fn ($q) => $q->byStatus($this->filterStatus))
            ->when($this->filterPosition, fn ($q) => $q->byPosition($this->filterPosition))
            ->when($this->searchTerm, fn ($q) => $q->where(function ($query) {
                $query->where('first_name', 'like', "%{$this->searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$this->searchTerm}%")
                    ->orWhere('email', 'like', "%{$this->searchTerm}%");
            })
            )
            ->recentFirst();

        return [
            'applications' => $query->paginate(15),
            'positions' => Position::orderBy('title')->get(),
            'statusCounts' => [
                'all' => Application::count(),
                'new' => Application::where('status', 'new')->count(),
                'reviewed' => Application::where('status', 'reviewed')->count(),
                'interview' => Application::where('status', 'interview')->count(),
                'offer' => Application::where('status', 'offer')->count(),
            ],
        ];
    }

    public function viewDetails(int $applicationId): void
    {
        $application = Application::with(['position', 'candidate', 'reviewedBy'])->findOrFail($applicationId);
        $this->viewingId = $application->id;
        $this->adminNotes = $application->admin_notes ?? '';
        $this->showDetailModal = true;
    }

    public function updateStatus(int $applicationId, string $status): void
    {
        $application = Application::findOrFail($applicationId);
        $application->updateStatus($status);

        // Send appropriate email based on status
        if ($status === 'rejected') {
            Mail::to($application->email)->send(new ApplicationRejected($application));
        } else {
            Mail::to($application->email)->send(new ApplicationStatusUpdated($application));
        }

        $this->dispatch('application-updated');
        $this->dispatch('notify', message: 'Application status updated successfully', type: 'success');
    }

    public function saveNotes(): void
    {
        $application = Application::findOrFail($this->viewingId);
        $application->update(['admin_notes' => $this->adminNotes]);

        $this->dispatch('application-updated');
        $this->dispatch('notify', message: 'Notes saved successfully', type: 'success');
    }

    public function markAsReviewed(int $applicationId): void
    {
        $application = Application::findOrFail($applicationId);
        $application->markAsReviewed(auth()->user());

        $this->dispatch('application-updated');
        $this->dispatch('notify', message: 'Application marked as reviewed', type: 'success');
    }

    public function downloadResume(int $applicationId): mixed
    {
        $application = Application::findOrFail($applicationId);

        if (! $application->resume_path || ! Storage::disk('private')->exists($application->resume_path)) {
            $this->dispatch('notify', message: 'Resume not found', type: 'error');

            return null;
        }

        return Storage::disk('private')->download($application->resume_path);
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedApplications = Application::pluck('id')->toArray();
        } else {
            $this->selectedApplications = [];
        }
    }

    public function bulkUpdateStatus(string $status): void
    {
        if (empty($this->selectedApplications)) {
            $this->dispatch('notify', message: 'No applications selected', type: 'warning');

            return;
        }

        $applications = Application::whereIn('id', $this->selectedApplications)->get();

        foreach ($applications as $application) {
            $application->updateStatus($status);

            // Send appropriate email based on status
            if ($status === 'rejected') {
                Mail::to($application->email)->send(new ApplicationRejected($application));
            } else {
                Mail::to($application->email)->send(new ApplicationStatusUpdated($application));
            }
        }

        $this->selectedApplications = [];
        $this->selectAll = false;
        $this->dispatch('application-updated');
        $this->dispatch('notify', message: 'Applications updated successfully', type: 'success');
    }

    public function deleteApplication(int $applicationId): void
    {
        Application::findOrFail($applicationId)->delete();
        $this->dispatch('application-deleted');
        $this->dispatch('notify', message: 'Application deleted', type: 'success');
    }

    #[On('application-updated')]
    #[On('application-deleted')]
    public function refreshList(): void
    {
        // Triggers re-render
    }
}; ?>

<div>
    <x-header title="Applications" subtitle="Manage job applications and candidates" icon="o-document-text" separator>
        <x-slot:actions>
            @if(count($selectedApplications) > 0)
                <x-dropdown label="Bulk Actions ({{ count($selectedApplications) }})" class="btn-primary">
                    <x-menu-item title="Mark as Reviewed" icon="o-check" wire:click="bulkUpdateStatus('reviewed')" />
                    <x-menu-item title="Schedule Interview" icon="o-calendar" wire:click="bulkUpdateStatus('interview')" />
                    <x-menu-item title="Send Offer" icon="o-hand-thumb-up" wire:click="bulkUpdateStatus('offer')" />
                    <x-menu-item title="Reject" icon="o-x-mark" wire:click="bulkUpdateStatus('rejected')" />
                </x-dropdown>
            @endif
        </x-slot:actions>
    </x-header>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
        <x-stat title="All Applications" :value="$statusCounts['all']" icon="o-document-text" />
        <x-stat title="New" :value="$statusCounts['new']" icon="o-bell-alert" class="text-blue-500" />
        <x-stat title="Reviewed" :value="$statusCounts['reviewed']" icon="o-eye" class="text-purple-500" />
        <x-stat title="Interviews" :value="$statusCounts['interview']" icon="o-users" class="text-orange-500" />
        <x-stat title="Offers" :value="$statusCounts['offer']" icon="o-hand-thumb-up" class="text-green-500" />
    </div>

    <!-- Filters -->
    <x-card class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-input
                label="Search"
                wire:model.live.debounce.300ms="searchTerm"
                icon="o-magnifying-glass"
                placeholder="Search by name or email..."
            />

            <x-select
                label="Status"
                wire:model.live="filterStatus"
                icon="o-funnel"
                :options="[
                    ['id' => '', 'name' => 'All Statuses'],
                    ['id' => 'new', 'name' => 'New'],
                    ['id' => 'reviewed', 'name' => 'Reviewed'],
                    ['id' => 'typing_test_sent', 'name' => 'Typing Test Sent'],
                    ['id' => 'typing_test_completed', 'name' => 'Typing Test Completed'],
                    ['id' => 'interview', 'name' => 'Interview'],
                    ['id' => 'offer', 'name' => 'Offer'],
                    ['id' => 'hired', 'name' => 'Hired'],
                    ['id' => 'rejected', 'name' => 'Rejected'],
                ]"
            />

            <x-select
                label="Position"
                wire:model.live="filterPosition"
                icon="o-briefcase"
                :options="[['id' => null, 'name' => 'All Positions'], ...$positions->map(fn($p) => ['id' => $p->id, 'name' => $p->title])->toArray()]"
            />

            <div class="flex items-end">
                <x-checkbox
                    label="Select All"
                    wire:model.live="selectAll"
                    wire:click="toggleSelectAll"
                />
            </div>
        </div>
    </x-card>

    <!-- Applications Table -->
    <x-card class="mt-6">
        <x-table :headers="[
            ['key' => 'select', 'label' => ''],
            ['key' => 'applicant', 'label' => 'Applicant'],
            ['key' => 'position', 'label' => 'Position'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'applied', 'label' => 'Applied'],
            ['key' => 'actions', 'label' => 'Actions'],
        ]" :rows="$applications">
            @scope('cell_select', $application)
                <x-checkbox wire:model.live="selectedApplications" :value="$application->id" />
            @endscope

            @scope('cell_applicant', $application)
                <div>
                    <div class="font-semibold">{{ $application->first_name }} {{ $application->last_name }}</div>
                    <div class="text-sm text-gray-600">{{ $application->email }}</div>
                    @if($application->phone)
                        <div class="text-sm text-gray-600">{{ $application->phone }}</div>
                    @endif
                </div>
            @endscope

            @scope('cell_position', $application)
                <div>
                    <div class="font-medium">{{ $application->position->title }}</div>
                    @if($application->position->department)
                        <div class="text-sm text-gray-600">{{ $application->position->department }}</div>
                    @endif
                </div>
            @endscope

            @scope('cell_status', $application)
                <x-badge
                    :value="str_replace('_', ' ', ucwords($application->status))"
                    class="{{ match($application->status) {
                        'new' => 'badge-primary',
                        'reviewed' => 'badge-info',
                        'typing_test_sent' => 'badge-warning',
                        'typing_test_completed' => 'badge-success',
                        'interview' => 'badge-accent',
                        'offer' => 'badge-success',
                        'hired' => 'badge-success',
                        'rejected' => 'badge-error',
                        default => 'badge-ghost',
                    } }}"
                />
            @endscope

            @scope('cell_applied', $application)
                <div class="text-sm">
                    <div>{{ $application->created_at->format('M d, Y') }}</div>
                    <div class="text-gray-600">{{ $application->created_at->format('g:i A') }}</div>
                </div>
            @endscope

            @scope('cell_actions', $application)
                <div class="flex gap-2">
                    <x-button
                        label="View"
                        icon="o-eye"
                        wire:click="viewDetails({{ $application->id }})"
                        class="btn-sm btn-ghost"
                    />

                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="o-ellipsis-horizontal" class="btn-sm btn-ghost" />
                        </x-slot:trigger>

                        @if($application->status === 'new')
                            <x-menu-item title="Mark as Reviewed" icon="o-check" wire:click="markAsReviewed({{ $application->id }})" />
                        @endif

                        <x-menu-item title="Schedule Interview" icon="o-calendar" wire:click="updateStatus({{ $application->id }}, 'interview')" />
                        <x-menu-item title="Send Offer" icon="o-hand-thumb-up" wire:click="updateStatus({{ $application->id }}, 'offer')" />
                        <x-menu-separator />
                        <x-menu-item title="Reject" icon="o-x-mark" wire:click="updateStatus({{ $application->id }}, 'rejected')" />
                        <x-menu-separator />
                        <x-menu-item
                            title="Delete"
                            icon="o-trash"
                            wire:click="deleteApplication({{ $application->id }})"
                            wire:confirm="Are you sure you want to delete this application?"
                        />
                    </x-dropdown>
                </div>
            @endscope
        </x-table>

        @if($applications->isEmpty())
            <div class="text-center py-12">
                <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                <h3 class="text-lg font-semibold mb-2">No applications found</h3>
                <p class="text-gray-600">Applications will appear here once candidates start applying.</p>
            </div>
        @endif
    </x-card>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $applications->links() }}
    </div>

    <!-- Detail Modal -->
    @if($viewingId)
        @php
            $application = Application::with(['position', 'candidate', 'reviewedBy'])->find($viewingId);
        @endphp

        <x-modal wire:model="showDetailModal" title="Application Details" subtitle="{{ $application->first_name }} {{ $application->last_name }}">
            <div class="space-y-6">
                <!-- Applicant Information -->
                <div>
                    <h4 class="font-semibold text-lg mb-3">Applicant Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Name</label>
                            <p class="font-medium">{{ $application->first_name }} {{ $application->last_name }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Email</label>
                            <p class="font-medium">{{ $application->email }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Phone</label>
                            <p class="font-medium">{{ $application->phone ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Location</label>
                            <p class="font-medium">{{ $application->location ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Position Applied For -->
                <div>
                    <h4 class="font-semibold text-lg mb-3">Position Applied For</h4>
                    <div class="bg-base-200 rounded-lg p-4">
                        <p class="font-semibold">{{ $application->position->title }}</p>
                        @if($application->position->department)
                            <p class="text-sm text-gray-600">{{ $application->position->department }}</p>
                        @endif
                    </div>
                </div>

                <!-- Links -->
                @if($application->portfolio_url || $application->linkedin_url || $application->github_url)
                    <div>
                        <h4 class="font-semibold text-lg mb-3">Links</h4>
                        <div class="flex flex-wrap gap-2">
                            @if($application->portfolio_url)
                                <a href="{{ $application->portfolio_url }}" target="_blank" class="btn btn-sm btn-outline">
                                    <x-icon name="o-globe-alt" class="w-4 h-4" />
                                    Portfolio
                                </a>
                            @endif
                            @if($application->linkedin_url)
                                <a href="{{ $application->linkedin_url }}" target="_blank" class="btn btn-sm btn-outline">
                                    <x-icon name="o-user-circle" class="w-4 h-4" />
                                    LinkedIn
                                </a>
                            @endif
                            @if($application->github_url)
                                <a href="{{ $application->github_url }}" target="_blank" class="btn btn-sm btn-outline">
                                    <x-icon name="o-code-bracket" class="w-4 h-4" />
                                    GitHub
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Cover Letter -->
                @if($application->cover_letter)
                    <div>
                        <h4 class="font-semibold text-lg mb-3">Cover Letter</h4>
                        <div class="bg-base-200 rounded-lg p-4">
                            <p class="whitespace-pre-wrap text-sm">{{ $application->cover_letter }}</p>
                        </div>
                    </div>
                @endif

                <!-- Resume -->
                @if($application->resume_path)
                    <div>
                        <h4 class="font-semibold text-lg mb-3">Resume</h4>
                        <x-button
                            label="Download Resume"
                            icon="o-document-arrow-down"
                            wire:click="downloadResume({{ $application->id }})"
                            class="btn-outline"
                        />
                    </div>
                @endif

                <!-- Screening Answers -->
                @if($application->screening_answers && count($application->screening_answers) > 0)
                    <div>
                        <h4 class="font-semibold text-lg mb-3">Screening Answers</h4>
                        <div class="space-y-3">
                            @foreach($application->screening_answers as $questionId => $answer)
                                @php
                                    $question = $application->position->questions()->find($questionId);
                                @endphp
                                @if($question)
                                    <div class="bg-base-200 rounded-lg p-3">
                                        <p class="font-medium text-sm mb-1">{{ $question->question }}</p>
                                        <p class="text-sm">{{ is_array($answer) ? implode(', ', $answer) : $answer }}</p>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @if($application->screening_score !== null)
                            <div class="mt-3">
                                <x-badge value="Score: {{ $application->screening_score }}" class="badge-primary" />
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Admin Notes -->
                <div>
                    <h4 class="font-semibold text-lg mb-3">Admin Notes</h4>
                    <x-textarea
                        wire:model="adminNotes"
                        placeholder="Add notes about this applicant..."
                        rows="4"
                    />
                    <x-button
                        label="Save Notes"
                        icon="o-check"
                        wire:click="saveNotes"
                        class="btn-primary btn-sm mt-2"
                    />
                </div>

                <!-- Status & Review Info -->
                <div>
                    <h4 class="font-semibold text-lg mb-3">Application Status</h4>
                    <div class="flex flex-wrap gap-2">
                        <x-badge
                            :value="str_replace('_', ' ', ucwords($application->status))"
                            class="badge-lg"
                        />
                        @if($application->reviewed_at)
                            <x-badge
                                value="Reviewed by {{ $application->reviewedBy->name }} on {{ $application->reviewed_at->format('M d, Y') }}"
                                class="badge-ghost"
                            />
                        @endif
                    </div>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Close" @click="$wire.showDetailModal = false" />
                <x-dropdown label="Change Status" class="btn-primary">
                    <x-menu-item title="Reviewed" wire:click="updateStatus({{ $application->id }}, 'reviewed')" />
                    <x-menu-item title="Interview" wire:click="updateStatus({{ $application->id }}, 'interview')" />
                    <x-menu-item title="Offer" wire:click="updateStatus({{ $application->id }}, 'offer')" />
                    <x-menu-item title="Hired" wire:click="updateStatus({{ $application->id }}, 'hired')" />
                    <x-menu-separator />
                    <x-menu-item title="Rejected" wire:click="updateStatus({{ $application->id }}, 'rejected')" />
                </x-dropdown>
            </x-slot:actions>
        </x-modal>
    @endif
</div>
