<?php

use Livewire\Volt\Component;
use App\Models\TypingTextSample;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $title = '';
    public string $content = '';
    public string $difficulty = 'medium';
    public bool $is_active = true;

    public function with(): array
    {
        return [
            'samples' => TypingTextSample::latest()->paginate(10),
        ];
    }

    public function openModal(?int $sampleId = null): void
    {
        if ($sampleId) {
            $sample = TypingTextSample::findOrFail($sampleId);
            $this->editingId = $sample->id;
            $this->title = $sample->title;
            $this->content = $sample->content;
            $this->difficulty = $sample->difficulty;
            $this->is_active = $sample->is_active;
        } else {
            $this->resetForm();
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:50',
            'difficulty' => 'required|in:easy,medium,hard',
            'is_active' => 'boolean',
        ]);

        if ($this->editingId) {
            $sample = TypingTextSample::findOrFail($this->editingId);
            $sample->update($validated);
        } else {
            TypingTextSample::create($validated);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('sample-saved');
    }

    public function toggleActive(int $sampleId): void
    {
        $sample = TypingTextSample::findOrFail($sampleId);
        $sample->update(['is_active' => !$sample->is_active]);
        $this->dispatch('sample-updated');
    }

    public function deleteSample(int $sampleId): void
    {
        TypingTextSample::findOrFail($sampleId)->delete();
        $this->dispatch('sample-deleted');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->content = '';
        $this->difficulty = 'medium';
        $this->is_active = true;
        $this->resetValidation();
    }

    #[On('sample-saved')]
    #[On('sample-updated')]
    #[On('sample-deleted')]
    public function refreshList(): void
    {
        // Triggers re-render
    }
}; ?>

<div>
    <x-header title="Typing Text Samples" subtitle="Manage test content for typing assessments" icon="o-document-text" separator>
        <x-slot:actions>
            <x-button label="Add Sample" icon="o-plus" class="btn-primary" wire:click="openModal" />
        </x-slot:actions>
    </x-header>

    <!-- Samples Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        @forelse ($samples as $sample)
            <x-card wire:key="sample-{{ $sample->id }}">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg">{{ $sample->title }}</h3>
                        <div class="flex gap-2 mt-2">
                            <x-badge
                                :value="ucfirst($sample->difficulty)"
                                class="{{ match($sample->difficulty) {
                                    'easy' => 'badge-success',
                                    'medium' => 'badge-warning',
                                    'hard' => 'badge-error',
                                } }}"
                            />
                            <x-badge :value="$sample->word_count . ' words'" class="badge-ghost" />
                            @if ($sample->is_active)
                                <x-badge value="Active" class="badge-primary" />
                            @else
                                <x-badge value="Inactive" class="badge-neutral" />
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-base-200 rounded-lg p-4 mb-4">
                    <p class="text-sm line-clamp-3">{{ $sample->content }}</p>
                </div>

                <div class="flex gap-2 justify-end">
                    <x-button
                        label="Edit"
                        icon="o-pencil"
                        wire:click="openModal({{ $sample->id }})"
                        class="btn-sm"
                    />
                    <x-button
                        :label="$sample->is_active ? 'Deactivate' : 'Activate'"
                        :icon="$sample->is_active ? 'o-eye-slash' : 'o-eye'"
                        wire:click="toggleActive({{ $sample->id }})"
                        class="btn-sm {{ $sample->is_active ? 'btn-warning' : 'btn-success' }}"
                    />
                    <x-button
                        icon="o-trash"
                        wire:click="deleteSample({{ $sample->id }})"
                        wire:confirm="Are you sure you want to delete this sample?"
                        class="btn-sm btn-error"
                    />
                </div>
            </x-card>
        @empty
            <div class="col-span-2">
                <x-card>
                    <div class="text-center py-12">
                        <x-icon name="o-document-text" class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                        <h3 class="text-lg font-semibold mb-2">No typing samples yet</h3>
                        <p class="text-gray-600 mb-4">Create your first typing test sample to get started.</p>
                        <x-button label="Add Sample" icon="o-plus" class="btn-primary" wire:click="openModal" />
                    </div>
                </x-card>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $samples->links() }}
    </div>

    <!-- Modal -->
    <x-modal wire:model="showModal" :title="$editingId ? 'Edit Sample' : 'Add Sample'" subtitle="Typing test content" class="backdrop-blur">
        <x-form wire:submit="save">
            <x-input
                label="Title"
                wire:model="title"
                icon="o-document-text"
                placeholder="e.g., Professional Communication - Medium"
                hint="Descriptive name for this sample"
            />

            <x-select
                label="Difficulty"
                wire:model="difficulty"
                icon="o-signal"
                :options="[
                    ['id' => 'easy', 'name' => 'Easy'],
                    ['id' => 'medium', 'name' => 'Medium'],
                    ['id' => 'hard', 'name' => 'Hard'],
                ]"
            />

            <x-textarea
                label="Content"
                wire:model="content"
                placeholder="Type or paste the text that candidates will type..."
                rows="8"
                hint="Minimum 50 characters. Word count will be calculated automatically."
            />

            <x-checkbox
                label="Active"
                wire:model="is_active"
                hint="Only active samples can be used in tests"
            />

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showModal = false" />
                <x-button label="{{ $editingId ? 'Update' : 'Create' }}" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
