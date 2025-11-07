<?php

use App\Models\TestInvitation;
use App\Models\TypingTest;
use App\Models\TypingTextSample;
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
        // This method is no longer needed as typing is handled client-side
        // Kept as a placeholder in case of fallback needs
    }

    public function updatedTypedText(): void
    {
        // This method is no longer needed as typing is handled client-side
        // Kept as a placeholder in case of fallback needs
    }

    public function calculateLiveWpm(): void
    {
        // This method is no longer needed - WPM calculated in JavaScript
        // Kept as a placeholder in case of fallback needs
    }

    public function updateTimer(): void
    {
        // This method is no longer needed - timer handled in JavaScript
        // Kept as a placeholder in case of fallback needs
    }

    public function submitTest(): void
    {
        // This method is deprecated - use saveTestResults() instead
        // Kept for backward compatibility only
    }

    // New method for JavaScript to submit results
    public function saveTestResults(array $results): void
    {
        $this->typedText = $results['typedText'];
        $this->elapsedSeconds = $results['duration'];

        // Analyze the typing
        $analysis = TypingTest::analyzeTyping(
            $this->textSample->content,
            $this->typedText
        );

        $this->totalCharacters = $analysis['total_characters'];
        $this->correctCharacters = $analysis['correct_characters'];
        $this->incorrectCharacters = $analysis['incorrect_characters'];
        $this->wpm = $results['wpm'];
        $this->accuracy = $results['accuracy'];

        // Save the test
        TypingTest::create([
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
            'keystroke_data' => [],
            'started_at' => now()->subSeconds($this->elapsedSeconds),
            'completed_at' => now(),
        ]);

        // Mark invitation as completed
        $this->invitation->markAsCompleted();
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
                            <span><strong>You have 60 seconds</strong> to type as much of the text as you can</span>
                        </li>
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
                            <span>The timer starts automatically when you begin typing</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>The test auto-submits after 60 seconds or you can submit early</span>
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
                    startTime: null,
                    elapsedSeconds: 0,
                    timer: null,
                    typedText: '',
                    liveWpm: 0,
                    testDuration: 60,
                    timerStarted: false,

                    init() {
                        // Don't start timer yet - wait for first keystroke
                        // Focus the textarea
                        this.$nextTick(() => {
                            this.$refs.typingInput.focus();
                        });
                    },

                    startTimer() {
                        if (!this.timerStarted) {
                            this.timerStarted = true;
                            this.startTime = Date.now();
                            this.timer = setInterval(() => {
                                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                                this.elapsedSeconds = elapsed;

                                // Calculate live WPM
                                if (elapsed > 0) {
                                    this.liveWpm = Math.floor((this.typedText.length / 5) / (elapsed / 60));
                                }

                                // Auto-submit after exactly 60 seconds
                                if (elapsed >= this.testDuration) {
                                    this.submitTest();
                                }
                            }, 1000);
                        }
                    },

                    onInput() {
                        // Start timer on first character
                        if (this.typedText.length === 1 && !this.timerStarted) {
                            this.startTimer();
                        }
                    },

                    destroy() {
                        if (this.timer) {
                            clearInterval(this.timer);
                        }
                    },

                    submitTest() {
                        if (this.timer) {
                            clearInterval(this.timer);
                        }

                        const finalElapsed = Math.floor((Date.now() - this.startTime) / 1000);
                        const finalWpm = Math.floor((this.typedText.length / 5) / (finalElapsed / 60));

                        // Calculate accuracy
                        const originalText = @js($textSample->content);
                        let correct = 0;
                        const minLength = Math.min(this.typedText.length, originalText.length);

                        for (let i = 0; i < minLength; i++) {
                            if (this.typedText[i] === originalText[i]) {
                                correct++;
                            }
                        }

                        const accuracy = minLength > 0 ? (correct / minLength) * 100 : 0;

                        // Submit to Livewire
                        $wire.saveTestResults({
                            typedText: this.typedText,
                            duration: finalElapsed,
                            wpm: finalWpm,
                            accuracy: Math.round(accuracy * 100) / 100
                        });
                    }
                }"
            >
                <!-- Timer and Stats -->
                                <!-- Timer and Stats -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <div class="flex items-center gap-6">
                        <div>
                            <div class="text-sm text-gray-500">Time Remaining</div>
                            <div class="text-2xl font-bold"
                                :class="timerStarted && elapsedSeconds >= 50 ? 'text-red-600' : (timerStarted ? 'text-gray-900' : 'text-blue-600')"
                                x-text="timerStarted ? ((testDuration - elapsedSeconds) + 's') : 'Ready'">
                            </div>
                        </div>
                        <div class="border-l-2 pl-6">
                            <div class="text-sm text-gray-500">Live WPM</div>
                            <div class="text-2xl font-bold text-blue-600" x-text="liveWpm">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 text-right">Progress</div>
                        <div class="text-lg text-gray-600" x-text="typedText.length + ' / ' + @js(strlen($textSample->content))">
                        </div>
                    </div>
                </div>

                <!-- Time Progress Bar -->
                <div class="mb-6">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            class="h-full transition-all duration-1000 ease-linear"
                            :class="elapsedSeconds >= 50 ? 'bg-red-500' : 'bg-blue-500'"
                            :style="`width: ${(elapsedSeconds / testDuration) * 100}%`"
                        ></div>
                    </div>
                </div>

                <!-- Text to Type with Real-time Highlighting (Condensed View) -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border-2 border-gray-300 select-none overflow-hidden">
                    <div
                        class="text-2xl leading-loose font-mono whitespace-pre-wrap wrap-break-word"
                        x-data="{
                            originalText: @js($textSample->content),
                            scrollToPosition() {
                                const currentChar = this.$el.querySelector('[data-index=\'' + this.typedText.length + '\']');
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
                                    'bg-green-200 text-green-900': typedText.length > {{ $index }} && typedText[{{ $index }}] === '{{ addslashes($char) }}',
                                    'bg-red-200 text-red-900': typedText.length > {{ $index }} && typedText[{{ $index }}] !== '{{ addslashes($char) }}',
                                    'bg-blue-300 text-blue-900 font-bold border-2 border-blue-500': typedText.length === {{ $index }},
                                    'text-gray-500': typedText.length < {{ $index }}
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
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2" x-text="'Type the text above (' + typedText.length + '/' + @js(strlen($textSample->content)) + ' characters)'">
                    </label>
                    <textarea
                        x-ref="typingInput"
                        x-model="typedText"
                        @input="onInput()"
                        class="w-full h-32 px-4 py-3 border-2 rounded-lg focus:border-blue-500 focus:ring focus:ring-blue-200 font-mono text-lg"
                        :class="$el === document.activeElement ? 'border-blue-500' : 'border-gray-300'"
                        placeholder="Start typing to begin timer..."
                        @paste.prevent
                        @copy.prevent
                        @cut.prevent
                        @drop.prevent
                        autocomplete="off"
                        spellcheck="false"
                    ></textarea>
                    <div class="flex justify-between items-center mt-1">
                        <p class="text-xs text-gray-500">
                            ⚠️ Copy/paste is disabled • Timer starts on first keystroke • Test auto-submits after 60 seconds
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
                        <span x-text="((typedText.length / @js(strlen($textSample->content))) * 100).toFixed(1) + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div
                            class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                            :style="`width: ${(typedText.length / @js(strlen($textSample->content))) * 100}%`"
                        ></div>
                    </div>
                </div>

                <!-- Manual Submit Button (Optional) -->
                <div class="flex justify-end">
                    <button
                        @click="submitTest()"
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
