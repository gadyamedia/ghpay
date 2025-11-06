<?php

use Livewire\Volt\Component;
use App\Models\Candidate;

new class extends Component {
    public Candidate $candidate;

    public function mount(int $candidateId): void
    {
        $this->candidate = Candidate::with(['typingTests.typingTextSample', 'testInvitations', 'createdBy'])
            ->findOrFail($candidateId);
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $candidate->name }}</h1>
                <p class="text-gray-600">{{ $candidate->email }}</p>
            </div>
            <a href="{{ route('admin.candidates.index') }}" class="text-blue-600 hover:text-blue-700">
                ← Back to Candidates
            </a>
        </div>

        <!-- Candidate Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Candidate Information</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <label class="text-sm text-gray-600">Position</label>
                    <p class="font-semibold">{{ $candidate->position_applied }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Phone</label>
                    <p class="font-semibold">{{ $candidate->phone ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Status</label>
                    <p>
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
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Invited At</label>
                    <p class="font-semibold">{{ $candidate->invited_at?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
            </div>

            @if ($candidate->notes)
                <div class="mt-6 pt-6 border-t">
                    <label class="text-sm text-gray-600">Notes</label>
                    <p class="mt-2 text-gray-900">{{ $candidate->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Performance Summary -->
        @if ($candidate->typingTests->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Performance Summary</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ $candidate->bestWpm }}</div>
                        <div class="text-sm text-gray-600 mt-1">Best WPM</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-green-600">{{ number_format($candidate->averageAccuracy, 1) }}%</div>
                        <div class="text-sm text-gray-600 mt-1">Avg Accuracy</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-purple-600">{{ $candidate->typingTests->count() }}</div>
                        <div class="text-sm text-gray-600 mt-1">Tests Taken</div>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-orange-600">{{ number_format($candidate->typingTests->avg('duration_seconds')) }}s</div>
                        <div class="text-sm text-gray-600 mt-1">Avg Duration</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Test Results -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Test Results</h2>
            </div>

            @if ($candidate->typingTests->isEmpty())
                <div class="p-6 text-center text-gray-500">
                    No tests completed yet.
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">WPM</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Accuracy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Difficulty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($candidate->typingTests as $test)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $test->completed_at->format('M d, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium">{{ $test->typingTextSample?->title ?? 'Custom Text' }}</div>
                                    <div class="text-xs text-gray-500">{{ $test->typingTextSample?->word_count ?? 0 }} words</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-lg font-semibold text-blue-600">{{ $test->wpm }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-lg font-semibold text-green-600">{{ number_format($test->accuracy, 1) }}%</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $test->duration_seconds }}s
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 text-xs font-semibold rounded-full capitalize
                                        {{ match($test->typingTextSample?->difficulty) {
                                            'easy' => 'bg-green-100 text-green-800',
                                            'medium' => 'bg-yellow-100 text-yellow-800',
                                            'hard' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        } }}
                                    ">
                                        {{ $test->typingTextSample?->difficulty ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="text-gray-600">
                                        {{ $test->correct_characters }}/{{ $test->total_characters }} chars
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Invitation History -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Invitation History</h2>
            </div>

            @if ($candidate->testInvitations->isEmpty())
                <div class="p-6 text-center text-gray-500">
                    No invitations sent yet.
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opened</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($candidate->testInvitations as $invitation)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $invitation->created_at->format('M d, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $invitation->opened_at?->format('M d, Y g:i A') ?? 'Not opened' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $invitation->completed_at?->format('M d, Y g:i A') ?? 'Not completed' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $invitation->expires_at->format('M d, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($invitation->completed_at)
                                        <span class="px-2 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Completed
                                        </span>
                                    @elseif ($invitation->isExpired())
                                        <span class="px-2 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Expired
                                        </span>
                                    @elseif ($invitation->opened_at)
                                        <span class="px-2 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            In Progress
                                        </span>
                                    @else
                                        <span class="px-2 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Sent
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
