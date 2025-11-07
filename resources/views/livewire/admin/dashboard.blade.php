<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Position;
use App\Models\TestInvitation;
use App\Models\TypingTest;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component
{
    public function with(): array
    {
        // Position Statistics
        $totalPositions = Position::count();
        $openPositions = Position::where('status', 'open')->count();
        $closedPositions = Position::where('status', 'closed')->count();
        $draftPositions = Position::where('status', 'draft')->count();

        // Application Statistics
        $totalApplications = Application::count();
        $newApplications = Application::where('status', 'new')->count();
        $reviewedApplications = Application::where('status', 'reviewed')->count();
        $interviewApplications = Application::where('status', 'interview')->count();
        $hiredApplications = Application::where('status', 'hired')->count();
        $rejectedApplications = Application::where('status', 'rejected')->count();

        // Applications this month
        $applicationsThisMonth = Application::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Candidate Statistics
        $totalCandidates = Candidate::count();
        $candidatesThisMonth = Candidate::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Typing Test Statistics
        $totalTests = TypingTest::count();
        $completedTests = TypingTest::whereNotNull('completed_at')->count();
        $averageWpm = TypingTest::whereNotNull('completed_at')->avg('wpm') ?? 0;
        $averageAccuracy = TypingTest::whereNotNull('completed_at')->avg('accuracy') ?? 0;

        // Test Invitations
        $pendingInvitations = TestInvitation::whereNull('completed_at')->count();
        $completedInvitations = TestInvitation::whereNotNull('completed_at')->count();

        // Recent Applications
        $recentApplications = Application::with(['position', 'candidate'])
            ->latest()
            ->take(10)
            ->get();

        // Top Positions by Applications
        $topPositions = Position::withCount('applications')
            ->orderBy('applications_count', 'desc')
            ->take(5)
            ->get();

        // Applications by Status (for chart)
        $applicationsByStatus = Application::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Monthly Application Trend (last 6 months)
        $monthlyTrend = Application::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('count(*) as count')
        )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            // Position Stats
            'totalPositions' => $totalPositions,
            'openPositions' => $openPositions,
            'closedPositions' => $closedPositions,
            'draftPositions' => $draftPositions,

            // Application Stats
            'totalApplications' => $totalApplications,
            'newApplications' => $newApplications,
            'reviewedApplications' => $reviewedApplications,
            'interviewApplications' => $interviewApplications,
            'hiredApplications' => $hiredApplications,
            'rejectedApplications' => $rejectedApplications,
            'applicationsThisMonth' => $applicationsThisMonth,

            // Candidate Stats
            'totalCandidates' => $totalCandidates,
            'candidatesThisMonth' => $candidatesThisMonth,

            // Test Stats
            'totalTests' => $totalTests,
            'completedTests' => $completedTests,
            'averageWpm' => round($averageWpm, 1),
            'averageAccuracy' => round($averageAccuracy, 1),
            'pendingInvitations' => $pendingInvitations,
            'completedInvitations' => $completedInvitations,

            // Recent Data
            'recentApplications' => $recentApplications,
            'topPositions' => $topPositions,

            // Chart Data
            'applicationsByStatus' => $applicationsByStatus,
            'monthlyTrend' => $monthlyTrend,
        ];
    }
}; ?>

<div>
    <x-header title="Recruitment Dashboard" subtitle="Overview of recruitment activities and statistics" icon="o-chart-bar" separator />

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Positions -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Positions</p>
                    <p class="text-3xl font-bold text-primary">{{ number_format($totalPositions) }}</p>
                    <div class="flex gap-2 mt-2 text-sm">
                        <span class="text-success">{{ $openPositions }} Open</span>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-600">{{ $draftPositions }} Draft</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                    <x-icon name="o-briefcase" class="w-8 h-8 text-primary" />
                </div>
            </div>
        </x-card>

        <!-- Total Applications -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Applications</p>
                    <p class="text-3xl font-bold text-info">{{ number_format($totalApplications) }}</p>
                    <div class="flex gap-2 mt-2 text-sm">
                        <span class="text-warning">{{ $newApplications }} New</span>
                        <span class="text-gray-400">•</span>
                        <span class="text-success">{{ $hiredApplications }} Hired</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-info/10 rounded-full flex items-center justify-center">
                    <x-icon name="o-inbox" class="w-8 h-8 text-info" />
                </div>
            </div>
        </x-card>

        <!-- Total Candidates -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Candidates</p>
                    <p class="text-3xl font-bold text-success">{{ number_format($totalCandidates) }}</p>
                    <div class="flex gap-2 mt-2 text-sm">
                        <span class="text-success">{{ $candidatesThisMonth }} This Month</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-success/10 rounded-full flex items-center justify-center">
                    <x-icon name="o-user-group" class="w-8 h-8 text-success" />
                </div>
            </div>
        </x-card>

        <!-- Typing Tests -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Typing Tests</p>
                    <p class="text-3xl font-bold text-warning">{{ number_format($completedTests) }}</p>
                    <div class="flex gap-2 mt-2 text-sm">
                        <span class="text-gray-600">{{ round($averageWpm) }} WPM Avg</span>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-600">{{ round($averageAccuracy) }}% Acc</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-warning/10 rounded-full flex items-center justify-center">
                    <x-icon name="o-pencil-square" class="w-8 h-8 text-warning" />
                </div>
            </div>
        </x-card>
    </div>

    <!-- Application Pipeline -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Application Status Breakdown -->
        <x-card>
            <h3 class="text-lg font-bold mb-4">Application Pipeline</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <span class="text-sm">New Applications</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ $newApplications }}</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $totalApplications > 0 ? ($newApplications / $totalApplications) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-sm">Under Review</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ $reviewedApplications }}</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $totalApplications > 0 ? ($reviewedApplications / $totalApplications) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                        <span class="text-sm">Interviews Scheduled</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ $interviewApplications }}</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $totalApplications > 0 ? ($interviewApplications / $totalApplications) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-sm">Hired</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ $hiredApplications }}</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ $totalApplications > 0 ? ($hiredApplications / $totalApplications) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <span class="text-sm">Rejected</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">{{ $rejectedApplications }}</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" style="width: {{ $totalApplications > 0 ? ($rejectedApplications / $totalApplications) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Top Positions -->
        <x-card>
            <h3 class="text-lg font-bold mb-4">Top Positions by Applications</h3>
            <div class="space-y-3">
                @forelse($topPositions as $position)
                    <div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
                        <div class="flex-1">
                            <p class="font-semibold">{{ $position->title }}</p>
                            <div class="flex gap-2 mt-1">
                                <x-badge :value="ucfirst($position->status)" class="badge-sm {{ $position->status === 'open' ? 'badge-success' : 'badge-ghost' }}" />
                                @if($position->department)
                                    <span class="text-xs text-gray-600">{{ $position->department }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-primary">{{ $position->applications_count }}</p>
                            <p class="text-xs text-gray-600">applications</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <x-icon name="o-briefcase" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>No positions yet</p>
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>

    <!-- Quick Actions -->
    <x-card class="mb-8">
        <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-button
                label="Add Position"
                icon="o-plus-circle"
                link="/admin/positions"
                class="btn-primary"
            />
            <x-button
                label="View Applications"
                icon="o-inbox"
                link="/admin/applications"
                class="btn-info"
            />
            <x-button
                label="Manage Candidates"
                icon="o-user-group"
                link="/admin/candidates"
                class="btn-success"
            />
            <x-button
                label="Typing Samples"
                icon="o-document-text"
                link="/admin/typing-samples"
                class="btn-warning"
            />
        </div>
    </x-card>

    <!-- Recent Applications -->
    <x-card>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold">Recent Applications</h3>
            <x-button
                label="View All"
                icon="o-arrow-right"
                link="/admin/applications"
                class="btn-sm btn-ghost"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentApplications as $application)
                        <tr>
                            <td>
                                <div>
                                    <p class="font-semibold">{{ $application->first_name }} {{ $application->last_name }}</p>
                                    <p class="text-xs text-gray-600">{{ $application->email }}</p>
                                </div>
                            </td>
                            <td>
                                <p class="font-medium">{{ $application->position->title }}</p>
                                @if($application->position->department)
                                    <p class="text-xs text-gray-600">{{ $application->position->department }}</p>
                                @endif
                            </td>
                            <td>
                                <x-badge
                                    :value="str_replace('_', ' ', ucfirst($application->status))"
                                    class="{{ match($application->status) {
                                        'new' => 'badge-warning',
                                        'reviewed' => 'badge-info',
                                        'interview' => 'badge-primary',
                                        'offer' => 'badge-success',
                                        'hired' => 'badge-success',
                                        'rejected' => 'badge-error',
                                        default => 'badge-ghost'
                                    } }}"
                                />
                            </td>
                            <td>
                                <p class="text-sm">{{ $application->created_at->format('M d, Y') }}</p>
                                <p class="text-xs text-gray-600">{{ $application->created_at->diffForHumans() }}</p>
                            </td>
                            <td>
                                @if($application->screening_score !== null)
                                    <div class="flex items-center gap-2">
                                        <div class="radial-progress text-primary" style="--value:{{ $application->screening_score }}; --size:2rem;">
                                            <span class="text-xs">{{ $application->screening_score }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">
                                No applications yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
