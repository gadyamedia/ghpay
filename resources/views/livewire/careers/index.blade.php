<?php

use App\Models\Position;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};

new
#[Title('Careers')]
#[Layout('components.layouts.public')]
class extends Component
{
    use WithPagination;

    public string $searchTerm = '';

    public string $filterDepartment = '';

    public string $filterEmploymentType = '';

    public string $filterLocationType = '';

    public function with(): array
    {
        $query = Position::query()
            ->open()
            ->with(['typingTextSample'])
            ->when($this->searchTerm, fn ($q) => $q->search($this->searchTerm))
            ->when($this->filterDepartment, fn ($q) => $q->byDepartment($this->filterDepartment))
            ->when($this->filterEmploymentType, fn ($q) => $q->byEmploymentType($this->filterEmploymentType))
            ->when($this->filterLocationType, fn ($q) => $q->byLocationType($this->filterLocationType))
            ->latest();

        return [
            'positions' => $query->paginate(12),
            'departments' => Position::open()->distinct()->pluck('department')->filter()->sort()->values(),
        ];
    }
}; ?>

<div class="min-h-screen bg-base-200">
    <!-- Hero Section -->
    <div class="bg-primary text-primary-content">
        <div class="container mx-auto px-4 py-16">
            <div class="max-w-3xl">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Join Our Team</h1>
                <p class="text-xl opacity-90">Discover exciting opportunities and grow your career with us</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Filters -->
        <x-card class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <x-input
                    label="Search"
                    wire:model.live.debounce.300ms="searchTerm"
                    icon="o-magnifying-glass"
                    placeholder="Search positions..."
                />

                <x-select
                    label="Department"
                    wire:model.live="filterDepartment"
                    icon="o-building-office"
                    :options="[['id' => '', 'name' => 'All Departments'], ...$departments->map(fn($d) => ['id' => $d, 'name' => $d])->toArray()]"
                />

                <x-select
                    label="Employment Type"
                    wire:model.live="filterEmploymentType"
                    icon="o-clock"
                    :options="[
                        ['id' => '', 'name' => 'All Types'],
                        ['id' => 'full-time', 'name' => 'Full-time'],
                        ['id' => 'part-time', 'name' => 'Part-time'],
                        ['id' => 'contract', 'name' => 'Contract'],
                        ['id' => 'internship', 'name' => 'Internship'],
                    ]"
                />

                <x-select
                    label="Location Type"
                    wire:model.live="filterLocationType"
                    icon="o-map-pin"
                    :options="[
                        ['id' => '', 'name' => 'All Locations'],
                        ['id' => 'remote', 'name' => 'Remote'],
                        ['id' => 'hybrid', 'name' => 'Hybrid'],
                        ['id' => 'onsite', 'name' => 'Onsite'],
                    ]"
                />
            </div>
        </x-card>

        <!-- Results Count -->
        <div class="mb-4">
            <p class="text-gray-600">
                Showing {{ $positions->count() }} of {{ $positions->total() }} open positions
            </p>
        </div>

        <!-- Positions Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @forelse ($positions as $position)
                <a href="{{ route('careers.show', $position->slug) }}" wire:key="position-{{ $position->id }}" class="block">
                    <x-card class="hover:shadow-lg transition-shadow cursor-pointer h-full">
                        <div class="h-full flex flex-col">
                            <!-- Header -->
                            <div class="mb-4">
                                <h3 class="text-xl font-bold mb-2">{{ $position->title }}</h3>
                                @if($position->department)
                                    <p class="text-gray-600 text-sm">{{ $position->department }}</p>
                                @endif
                            </div>

                            <!-- Badges -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <x-badge
                                    :value="str_replace('-', ' ', ucfirst($position->employment_type))"
                                    class="badge-primary"
                                />
                                <x-badge
                                    :value="ucfirst($position->location_type)"
                                    class="badge-info"
                                />
                                @if($position->location)
                                    <x-badge :value="$position->location" class="badge-ghost" />
                                @endif
                            </div>

                            <!-- Description Preview -->
                            <p class="text-sm text-gray-600 mb-4 line-clamp-3 flex-1">
                                {{ Str::limit(strip_tags($position->description), 150) }}
                            </p>

                            <!-- Footer -->
                            <div class="pt-4 border-t border-gray-200 mt-auto">
                                <div class="flex justify-between items-center">
                                    <div class="text-sm">
                                        @if($position->show_salary && $position->salary_min && $position->salary_max)
                                            <span class="font-semibold text-primary">
                                                ₱{{ number_format($position->salary_min) }} - ₱{{ number_format($position->salary_max) }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-primary font-medium text-sm flex items-center gap-1">
                                        View Details
                                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </x-card>
                </a>
            @empty
                <div class="col-span-full">
                    <x-card>
                        <div class="text-center py-16">
                            <x-icon name="o-briefcase" class="w-20 h-20 mx-auto mb-4 text-gray-400" />
                            <h3 class="text-2xl font-semibold mb-2">No positions available</h3>
                            <p class="text-gray-600 mb-4">Check back soon for new opportunities!</p>
                        </div>
                    </x-card>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $positions->links() }}
        </div>
    </div>
</div>
