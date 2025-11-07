<?php

use App\Mail\ApplicationReceived;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Position;
use App\Models\TestInvitation;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new
#[Title('Career')]
#[Layout('components.layouts.public')]
class extends Component
{
    use WithFileUploads;

    public Position $position;

    // Form fields
    #[Validate('required|string|max:255')]
    public string $first_name = '';

    #[Validate('required|string|max:255')]
    public string $last_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:255')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('nullable|string|max:5000')]
    public string $cover_letter = '';

    #[Validate('required|file|mimes:pdf,doc,docx|max:10240')] // 10MB max
    public $resume;

    #[Validate('nullable|url')]
    public string $linkedin_url = '';

    public array $screening_answers = [];

    public bool $submitted = false;

    public function mount(Position $position): void
    {
        // Check if position is open
        if ($position->status !== 'open') {
            abort(404);
        }

        $this->position = $position->load(['questions']);
        $this->position->incrementViews();
    }

    public function submit(): void
    {
        $this->validate();

        // Validate screening questions
        if ($this->position->questions->isNotEmpty()) {
            foreach ($this->position->questions as $question) {
                if ($question->is_required && empty($this->screening_answers[$question->id])) {
                    $this->addError("screening_answers.{$question->id}", 'This question is required');
                }
            }

            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        // Store resume
        $resumePath = $this->resume->store('resumes', 'private');

        // Create application
        $application = Application::create([
            'position_id' => $this->position->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,
            'cover_letter' => $this->cover_letter,
            'resume_path' => $resumePath,
            'portfolio_url' => null,
            'linkedin_url' => $this->linkedin_url ?: null,
            'github_url' => null,
            'screening_answers' => $this->screening_answers,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'new',
        ]);

        // Calculate screening score if applicable
        if (! empty($this->screening_answers)) {
            $score = $application->calculateScreeningScore();
            $application->update(['screening_score' => $score]);
        }

        // Create or find candidate
        $candidate = Candidate::firstOrCreate(
            ['email' => $this->email],
            [
                'name' => $this->first_name.' '.$this->last_name,
                'phone' => $this->phone,
            ]
        );

        // Link candidate to application
        $application->update(['candidate_id' => $candidate->id]);

        // Auto-send typing test if enabled
        if ($this->position->auto_send_typing_test && $this->position->require_typing_test) {
            $textSampleId = $this->position->typing_text_sample_id
                ?: \App\Models\TypingTextSample::where('is_active', true)->inRandomOrder()->first()?->id;

            if ($textSampleId) {
                TestInvitation::createForCandidate($candidate, $textSampleId);

                // Update application status
                $application->update(['status' => 'typing_test_sent']);
            }
        }

        // Increment position applications count
        $this->position->incrementApplications();

        // Send notification email if enabled
        if ($this->position->notify_admin_on_application) {
            $notificationEmail = $this->position->notification_email ?: config('mail.from.address');

            Mail::to($notificationEmail)->send(new ApplicationReceived($application));
        }

        $this->submitted = true;
    }
}; ?>

<div class="min-h-screen bg-base-200">
    @if($submitted)
        <!-- Success Message -->
        <div class="container mx-auto px-4 py-16">
            <x-card class="max-w-2xl mx-auto">
                <div class="text-center py-12">
                    <div class="mb-6">
                        <div class="w-20 h-20 bg-success rounded-full flex items-center justify-center mx-auto">
                            <x-icon name="o-check" class="w-12 h-12 text-white" />
                        </div>
                    </div>
                    <h2 class="text-3xl font-bold mb-4">Application Submitted!</h2>
                    <p class="text-lg text-gray-600 mb-6">
                        Thank you for applying to {{ $position->title }}. We've received your application and will review it shortly.
                    </p>

                    @if($position->auto_send_typing_test && $position->require_typing_test)
                        <div class="alert alert-info mb-6">
                            <x-icon name="o-envelope" class="w-6 h-6" />
                            <div class="text-left">
                                <p class="font-semibold">Typing Test Invitation Sent</p>
                                <p class="text-sm">Check your email for a link to complete the typing test.</p>
                            </div>
                        </div>
                    @endif

                    <div class="flex gap-4 justify-center">
                        <x-button
                            label="View All Positions"
                            link="/careers"
                            class="btn-primary"
                        />
                    </div>
                </div>
            </x-card>
        </div>
    @else
        <!-- Application Form -->
        <div class="bg-primary text-primary-content">
            <div class="container mx-auto px-4 py-12">
                <div class="max-w-3xl">
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Apply for {{ $position->title }}</h1>
                    @if($position->department)
                        <p class="text-lg opacity-90">{{ $position->department }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4 py-8">
            <div class="max-w-3xl mx-auto">
                <!-- Position Info -->
                <x-card class="mb-6">
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

                    @if($position->require_typing_test)
                        <div class="alert alert-warning">
                            <x-icon name="o-pencil-square" class="w-5 h-5" />
                            <div>
                                <p class="font-semibold">Typing Test Required</p>
                                <p class="text-sm">
                                    This position requires a typing test.
                                    @if($position->auto_send_typing_test)
                                        You'll receive an email with the test link after submitting your application.
                                    @endif
                                    @if($position->minimum_wpm)
                                        Minimum typing speed: {{ $position->minimum_wpm }} WPM
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
                </x-card>

                <!-- Application Form -->
                <x-card>
                    <x-form wire:submit="submit">
                        <!-- Personal Information -->
                        <div class="space-y-4">
                            <h3 class="text-xl font-semibold">Personal Information</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <x-input
                                    label="First Name"
                                    wire:model="first_name"
                                    icon="o-user"
                                    placeholder="John"
                                />

                                <x-input
                                    label="Last Name"
                                    wire:model="last_name"
                                    icon="o-user"
                                    placeholder="Doe"
                                />
                            </div>

                            <x-input
                                label="Email"
                                wire:model="email"
                                type="email"
                                icon="o-envelope"
                                placeholder="john@example.com"
                            />

                            <x-input
                                label="Phone"
                                wire:model="phone"
                                type="tel"
                                icon="o-phone"
                                placeholder="+1 (555) 123-4567"
                            />

                            <x-input
                                label="Location"
                                wire:model="location"
                                icon="o-map-pin"
                                placeholder="City, State"
                                hint="Optional"
                            />
                        </div>

                        <!-- Resume Upload -->
                        <div class="space-y-4 mt-6">
                            <h3 class="text-xl font-semibold">Resume</h3>

                            <x-file
                                wire:model="resume"
                                label="Upload Resume"
                                hint="PDF, DOC, or DOCX (Max 10MB)"
                                accept=".pdf,.doc,.docx"
                            />
                        </div>

                        <!-- Cover Letter -->
                        <div class="space-y-4 mt-6">
                            <h3 class="text-xl font-semibold">Cover Letter</h3>

                            <x-textarea
                                label="Tell us why you're interested in this position"
                                wire:model="cover_letter"
                                rows="6"
                                placeholder="Share your motivation and what makes you a great fit..."
                                hint="Optional"
                            />
                        </div>

                        <!-- Links -->
                        <div class="space-y-4 mt-6">
                            <h3 class="text-xl font-semibold">Links</h3>

                            <x-input
                                label="LinkedIn Profile"
                                wire:model="linkedin_url"
                                type="url"
                                icon="o-user-circle"
                                placeholder="https://linkedin.com/in/yourprofile"
                                hint="Optional"
                            />
                        </div>

                        <!-- Screening Questions -->
                        @if($position->questions->isNotEmpty())
                            <div class="space-y-4 mt-6">
                                <h3 class="text-xl font-semibold">Screening Questions</h3>

                                @foreach($position->questions as $question)
                                    <div wire:key="question-{{ $question->id }}">
                                        @if($question->type === 'text')
                                            <x-input
                                                :label="$question->question . ($question->is_required ? ' *' : '')"
                                                wire:model="screening_answers.{{ $question->id }}"
                                                placeholder="Your answer..."
                                            />
                                        @elseif($question->type === 'textarea')
                                            <x-textarea
                                                :label="$question->question . ($question->is_required ? ' *' : '')"
                                                wire:model="screening_answers.{{ $question->id }}"
                                                rows="4"
                                                placeholder="Your answer..."
                                            />
                                        @elseif($question->type === 'yes_no')
                                            <x-radio
                                                :label="$question->question . ($question->is_required ? ' *' : '')"
                                                wire:model="screening_answers.{{ $question->id }}"
                                                :options="[
                                                    ['id' => 'yes', 'name' => 'Yes'],
                                                    ['id' => 'no', 'name' => 'No'],
                                                ]"
                                            />
                                        @elseif($question->type === 'multiple_choice')
                                            <x-select
                                                :label="$question->question . ($question->is_required ? ' *' : '')"
                                                wire:model="screening_answers.{{ $question->id }}"
                                                :options="collect($question->options ?? [])->map(fn($opt) => ['id' => $opt, 'name' => $opt])->toArray()"
                                                placeholder="Select an option..."
                                            />
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Submit -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <div class="flex gap-4 justify-end">
                                <x-button
                                    label="Cancel"
                                    link="/careers"
                                />
                                <x-button
                                    label="Submit Application"
                                    type="submit"
                                    icon="o-paper-airplane"
                                    class="btn-primary"
                                    spinner="submit"
                                />
                            </div>
                        </div>
                    </x-form>
                </x-card>
            </div>
        </div>
    @endif
</div>
