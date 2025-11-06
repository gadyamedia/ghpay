<?php

use App\Models\TestInvitation;
use App\Models\TypingTest;
use App\Models\TypingTextSample;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public string $token = '';

    public ?TestInvitation $invitation = null;

    public ?TypingTextSample $textSample = null;

    public string $testStatus = 'not_started'; // not_started, in_progress, completed

    public string $typedText = '';

    public int $elapsedSeconds = 0;

    public bool $timerStarted = false;

    public array $keystrokeData = [];

    // Results
    public ?int $wpm = null;

    public ?int $liveWpm = 0;

    public ?float $accuracy = null;

    public ?int $totalCharacters = null;

    public ?int $correctCharacters = null;

    public ?int $incorrectCharacters = null;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->invitation = TestInvitation::with('typingTextSample')->where('token', $token)->first();

        if (! $this->invitation || ! $this->invitation->isValid()) {
            abort(403, 'Invalid or expired invitation.');
        }

        // Mark as opened
        $this->invitation->markAsOpened(
            request()->ip(),
            request()->userAgent()
        );

        // Use assigned test sample if available, otherwise get random
        if ($this->invitation->typing_text_sample_id && $this->invitation->typingTextSample) {
            $this->textSample = $this->invitation->typingTextSample;
        } else {
            $this->textSample = TypingTextSample::getRandomActive();
        }

        if (! $this->textSample) {
            abort(500, 'No typing samples available.');
        }
    }

    public function startTest(): void
    {
        $this->testStatus = 'in_progress';
        $this->typedText = '';
        $this->elapsedSeconds = 0;
        $this->timerStarted = false;
        $this->keystrokeData = [];
    }

    public function updated($property): void
    {
        if ($property === 'typedText') {
            // Start timer on first character
            if (! $this->timerStarted && strlen($this->typedText) > 0) {
                $this->timerStarted = true;
            }

            // Calculate live WPM
            $this->calculateLiveWpm();

            // Auto-submit when finished typing
            if (strlen($this->typedText) >= strlen($this->textSample->content)) {
                $this->submitTest();
            }
        }
    }

    public function calculateLiveWpm(): void
    {
        if ($this->elapsedSeconds > 0 && strlen($this->typedText) > 0) {
            // Analyze current typing to get correct characters
            $analysis = TypingTest::analyzeTyping(
                substr($this->textSample->content, 0, strlen($this->typedText)),
                $this->typedText
            );

            $correctChars = $analysis['correct_characters'];

            // Calculate WPM based on correct characters typed so far
            $this->liveWpm = TypingTest::calculateWpm(
                $correctChars,
                $this->elapsedSeconds
            );
        } else {
            $this->liveWpm = 0;
        }
    }

    #[On('test-tick')]
    public function updateTimer(): void
    {
        if ($this->testStatus === 'in_progress' && $this->timerStarted) {
            $this->elapsedSeconds++;
            $this->calculateLiveWpm();
        }
    }

    public function submitTest(): void
    {
        if ($this->testStatus !== 'in_progress') {
            return;
        }

        // Analyze the typing
        $analysis = TypingTest::analyzeTyping(
            $this->textSample->content,
            $this->typedText
        );

        $this->totalCharacters = $analysis['total_characters'];
        $this->correctCharacters = $analysis['correct_characters'];
        $this->incorrectCharacters = $analysis['incorrect_characters'];

        // Calculate metrics
        $this->wpm = TypingTest::calculateWpm(
            $this->correctCharacters,
            $this->elapsedSeconds
        );

        $this->accuracy = TypingTest::calculateAccuracy(
            $this->correctCharacters,
            $this->totalCharacters
        );

        // Save the test
        $test = TypingTest::create([
            'candidate_id' => $this->invitation->candidate_id,
            'typing_text_sample_id' => $this->textSample->id,
            'original_text' => $this->textSample->content,
            'typed_text' => $this->typedText,
            'wpm' => $this->wpm,
            'accuracy' => $this->accuracy,
            'duration_seconds' => $this->elapsedSeconds,
            'total_characters' => $this->totalCharacters,
            'correct_characters' => $this->correctCharacters,
            'incorrect_characters' => $this->incorrectCharacters,
            'keystroke_data' => $this->keystrokeData,
            'started_at' => now()->subSeconds($this->elapsedSeconds),
            'completed_at' => now(),
        ]);

        // Mark invitation as completed
        $this->invitation->markAsCompleted();

        // Update candidate status
        $this->invitation->candidate->update(['status' => 'completed']);

        $this->testStatus = 'completed';
    }

    public function recordKeystroke(array $keystroke): void
    {
        $this->keystrokeData[] = $keystroke;
    }
}; ?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        @if ($testStatus === 'not_started')
            <!-- Welcome Screen -->
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">
                    Welcome to the Typing Test
                </h1>
                <p class="text-lg text-gray-600 mb-8">
                    Hi {{ $invitation->candidate->name }}! You've been invited to complete a typing test
                    for the <strong>{{ $invitation->candidate->position_applied }}</strong> position.
                </p>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8 text-left">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Instructions:</h2>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Type the text exactly as shown, including punctuation and spacing</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Your words per minute (WPM) and accuracy will be calculated</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>The timer starts when you begin typing</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Click "Submit" when you're done or when time runs out</span>
                        </li>
                    </ul>
                </div>

                <button
                    wire:click="startTest"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200"
                >
                    Start Typing Test
                </button>

                <p class="text-sm text-gray-500 mt-4">
                    Invitation expires: {{ $invitation->expires_at->format('M d, Y g:i A') }}
                </p>
            </div>

        @elseif ($testStatus === 'in_progress')
            <!-- Typing Test Screen -->
            <div class="bg-white rounded-lg shadow-lg p-8"
                x-data="{
                    timer: null,
                    init() {
                        this.timer = setInterval(() => {
                            $wire.dispatch('test-tick');
                        }, 1000);
                    },
                    destroy() {
                        clearInterval(this.timer);
                    }
                }"
            >
                <!-- Timer and Stats -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <div class="flex items-center gap-6">
                        <div>
                            <div class="text-sm text-gray-500">Time</div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ floor($elapsedSeconds / 60) }}:{{ str_pad($elapsedSeconds % 60, 2, '0', STR_PAD_LEFT) }}
                            </div>
                        </div>
                        <div class="border-l-2 pl-6">
                            <div class="text-sm text-gray-500">Live WPM</div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ $liveWpm }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 text-right">Characters</div>
                        <div class="text-lg text-gray-600">
                            {{ strlen($typedText) }} / {{ strlen($textSample->content) }}
                        </div>
                    </div>
                </div>

                <!-- Text to Type with Real-time Highlighting (Condensed View) -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border-2 border-gray-300 select-none overflow-hidden">
                    <div
                        class="text-2xl leading-loose font-mono whitespace-pre-wrap break-words"
                        x-data="{
                            typed: @entangle('typedText').live,
                            scrollToPosition() {
                                const currentChar = this.$el.querySelector('[data-index=\'' + this.typed.length + '\']');
                                if (currentChar) {
                                    currentChar.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            }
                        }"
                        x-effect="scrollToPosition()"
                        style="max-height: 200px; overflow-y: auto; scroll-behavior: smooth;"
                        id="text-display"
                    >
                        @php
                            $chars = mb_str_split($textSample->content);
                        @endphp
                        @foreach ($chars as $index => $char)<span
                                data-index="{{ $index }}"
                                x-bind:class="{
                                    'bg-green-200 text-green-900': typed.length > {{ $index }} && typed[{{ $index }}] === '{{ addslashes($char) }}',
                                    'bg-red-200 text-red-900': typed.length > {{ $index }} && typed[{{ $index }}] !== '{{ addslashes($char) }}',
                                    'bg-blue-300 text-blue-900 font-bold border-2 border-blue-500': typed.length === {{ $index }},
                                    'text-gray-500': typed.length < {{ $index }}
                                }"
                                class="transition-colors duration-100 inline-block"
                                style="min-width: {{ $char === ' ' ? '0.5em' : 'auto' }};"
                            >@if($char === ' ')&nbsp;@else{{ $char }}@endif</span>@endforeach
                    </div>
                    <div class="mt-2 text-xs text-gray-500 text-center">
                        Text automatically scrolls as you type • Focus follows your position
                    </div>
                </div>

                <!-- Typing Area (Hidden) -->
                <div class="mb-6" x-data="{ focused: false }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Type the text above ({{ strlen($typedText) }}/{{ strlen($textSample->content) }} characters)
                    </label>
                    <textarea
                        wire:model.live="typedText"
                        class="w-full h-32 px-4 py-3 border-2 rounded-lg focus:border-blue-500 focus:ring focus:ring-blue-200 font-mono text-lg"
                        :class="focused ? 'border-blue-500' : 'border-gray-300'"
                        placeholder="Start typing..."
                        autofocus
                        @focus="focused = true"
                        @blur="focused = false"
                        onpaste="return false;"
                        oncopy="return false;"
                        oncut="return false;"
                        ondrop="return false;"
                        autocomplete="off"
                        spellcheck="false"
                    ></textarea>
                    <div class="flex justify-between items-center mt-1">
                        <p class="text-xs text-gray-500">
                            ⚠️ Copy/paste is disabled • Test auto-submits when complete
                        </p>
                        <div class="flex gap-4 text-xs">
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 bg-green-200 rounded"></span>
                                <span>Correct</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 bg-red-200 rounded"></span>
                                <span>Wrong</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 bg-blue-300 border-2 border-blue-500 rounded"></span>
                                <span>Current</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Progress</span>
                        <span>{{ number_format((strlen($typedText) / strlen($textSample->content)) * 100, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div
                            class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                            style="width: {{ (strlen($typedText) / strlen($textSample->content)) * 100 }}%"
                        ></div>
                    </div>
                </div>

                <!-- Manual Submit Button (Optional) -->
                <div class="flex justify-end">
                    <button
                        wire:click="submitTest"
                        class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200"
                    >
                        Submit Test Early
                    </button>
                </div>
            </div>

        @else
            <!-- Results Screen -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Test Complete!
                    </h1>
                    <p class="text-lg text-gray-600">
                        Thank you for completing the typing test, {{ $invitation->candidate->name }}.
                    </p>
                </div>

                <!-- Results Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-50 rounded-lg p-6 text-center">
                        <div class="text-4xl font-bold text-blue-600 mb-2">{{ $wpm }}</div>
                        <div class="text-sm text-gray-600">Words Per Minute</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-6 text-center">
                        <div class="text-4xl font-bold text-green-600 mb-2">{{ number_format($accuracy, 1) }}%</div>
                        <div class="text-sm text-gray-600">Accuracy</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-6 text-center">
                        <div class="text-4xl font-bold text-purple-600 mb-2">{{ floor($elapsedSeconds / 60) }}:{{ str_pad($elapsedSeconds % 60, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="text-sm text-gray-600">Time Taken</div>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-6 text-center">
                        <div class="text-4xl font-bold text-orange-600 mb-2">{{ $totalCharacters }}</div>
                        <div class="text-sm text-gray-600">Total Characters</div>
                    </div>
                </div>

                <!-- Detailed Stats -->
                <div class="bg-gray-50 rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Detailed Statistics</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Correct Characters:</span>
                            <span class="font-semibold text-green-600">{{ $correctCharacters }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Incorrect Characters:</span>
                            <span class="font-semibold text-red-600">{{ $incorrectCharacters }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Test Duration:</span>
                            <span class="font-semibold">{{ $elapsedSeconds }} seconds</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Sample Difficulty:</span>
                            <span class="font-semibold capitalize">{{ $textSample->difficulty }}</span>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <p class="text-gray-700">
                        Your results have been submitted to our team. We'll be in touch soon regarding the next steps in your application.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
