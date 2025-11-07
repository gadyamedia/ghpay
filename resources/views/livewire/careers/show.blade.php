<?php

use App\Models\Position;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public Position $position;

    public function mount(Position $position): void
    {
        // Check if position is open
        if ($position->status !== 'open') {
            abort(404);
        }

        $this->position = $position->load(['typingTextSample']);

        // Increment view count
        $this->position->incrementViews();
    }
}; ?>

<div class="min-h-screen bg-base-200">
    <!-- Header -->
    <div class="bg-primary text-primary-content">
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-4xl">
                <!-- Back Button -->
                <a href="{{ route('careers.index') }}" class="inline-flex items-center gap-2 text-primary-content/80 hover:text-primary-content mb-6 transition-colors">
                    <x-icon name="o-arrow-left" class="w-5 h-5" />
                    <span>Back to All Positions</span>
                </a>

                <h1 class="text-3xl md:text-4xl font-bold mb-4">{{ $position->title }}</h1>
                
                @if($position->department)
                    <p class="text-xl opacity-90 mb-4">{{ $position->department }}</p>
                @endif

                <div class="flex flex-wrap gap-3">
                    <x-badge
                        :value="str_replace('-', ' ', ucfirst($position->employment_type))"
                        class="badge-lg bg-white/20 text-white border-white/30"
                    />
                    <x-badge
                        :value="ucfirst($position->location_type)"
                        class="badge-lg bg-white/20 text-white border-white/30"
                    />
                    @if($position->location)
                        <x-badge 
                            :value="$position->location" 
                            class="badge-lg bg-white/20 text-white border-white/30" 
                        />
                    @endif
                    @if($position->require_typing_test)
                        <x-badge 
                            value="Typing Test Required" 
                            class="badge-lg bg-warning/20 text-white border-warning/30" 
                        />
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-7xl">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Description -->
                <x-card>
                    <h2 class="text-2xl font-bold mb-4">About the Role</h2>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        {!! nl2br(e($position->description)) !!}
                    </div>
                </x-card>

                <!-- Responsibilities -->
                @if($position->responsibilities)
                    <x-card>
                        <h2 class="text-2xl font-bold mb-4">Responsibilities</h2>
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e($position->responsibilities)) !!}
                        </div>
                    </x-card>
                @endif

                <!-- Requirements -->
                @if($position->requirements)
                    <x-card>
                        <h2 class="text-2xl font-bold mb-4">Requirements</h2>
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e($position->requirements)) !!}
                        </div>
                    </x-card>
                @endif

                <!-- Benefits -->
                @if($position->benefits)
                    <x-card>
                        <h2 class="text-2xl font-bold mb-4">Benefits</h2>
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e($position->benefits)) !!}
                        </div>
                    </x-card>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Apply Card -->
                <x-card>
                    <h3 class="text-xl font-bold mb-4">Ready to Apply?</h3>
                    
                    @if($position->show_salary && $position->salary_min && $position->salary_max)
                        <div class="mb-6 p-4 bg-primary/5 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Salary Range</p>
                            <p class="text-2xl font-bold text-primary">
                                ₱{{ number_format($position->salary_min) }} - ₱{{ number_format($position->salary_max) }}
                            </p>
                            <p class="text-sm text-gray-600">per month</p>
                        </div>
                    @endif

                    @if($position->application_deadline)
                        <div class="alert alert-info mb-4">
                            <x-icon name="o-calendar" class="w-5 h-5" />
                            <div>
                                <p class="text-sm font-semibold">Application Deadline</p>
                                <p class="text-sm">{{ $position->application_deadline->format('F d, Y') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($position->require_typing_test)
                        <div class="alert alert-warning mb-4">
                            <x-icon name="o-pencil-square" class="w-5 h-5" />
                            <div>
                                <p class="text-sm font-semibold">Typing Test Required</p>
                                <p class="text-sm">
                                    This position requires a typing test.
                                    @if($position->minimum_wpm)
                                        Minimum speed: {{ $position->minimum_wpm }} WPM
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    <x-button
                        label="Apply for this Position"
                        icon="o-paper-airplane"
                        link="{{ route('careers.apply', $position->slug) }}"
                        class="btn-primary w-full btn-lg"
                    />

                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <x-icon name="o-eye" class="w-4 h-4" />
                                <span>{{ number_format($position->views_count) }} views</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-icon name="o-user-group" class="w-4 h-4" />
                                <span>{{ number_format($position->applications_count) }} applicants</span>
                            </div>
                        </div>
                    </div>
                </x-card>

                <!-- Share Card -->
                <x-card x-data="{ copied: false }">
                    <h3 class="text-lg font-bold mb-3">Share this Position</h3>
                    <div class="flex gap-2">
                        <button 
                            type="button"
                            class="btn btn-sm flex-1"
                            @click="navigator.clipboard.writeText(window.location.href).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                            x-text="copied ? 'Copied!' : 'Copy Link'"
                        >
                        </button>
                        <a href="mailto:?subject={{ urlencode($position->title . ' - Job Opening') }}&body={{ urlencode('Check out this job opportunity: ' . request()->url()) }}" class="btn btn-sm flex-1 flex items-center justify-center gap-2">
                            <x-icon name="o-envelope" class="w-4 h-4" />
                            <span>Email</span>
                        </a>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</div>
