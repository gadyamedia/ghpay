<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingTest extends Model
{
    protected $fillable = [
        'candidate_id',
        'typing_text_sample_id',
        'original_text',
        'typed_text',
        'wpm',
        'accuracy',
        'duration_seconds',
        'total_characters',
        'correct_characters',
        'incorrect_characters',
        'keystroke_data',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'keystroke_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function typingTextSample(): BelongsTo
    {
        return $this->belongsTo(TypingTextSample::class);
    }

    /**
     * Calculate WPM (Words Per Minute)
     * Standard: 1 word = 5 characters (industry standard)
     *
     * Gross WPM = (All Typed Characters / 5) / Time in Minutes
     * Net WPM = Gross WPM - (Errors / Time in Minutes)
     *
     * This calculates Gross WPM (raw typing speed)
     *
     * @param  int  $totalCharacters  Total characters typed (not just correct ones)
     * @param  int  $durationSeconds  Time taken in seconds
     * @return int WPM rounded down
     */
    public static function calculateWpm(int $totalCharacters, int $durationSeconds): int
    {
        if ($durationSeconds === 0) {
            return 0;
        }

        $minutes = $durationSeconds / 60;
        $words = $totalCharacters / 5;

        return (int) floor($words / $minutes);
    }

    /**
     * Calculate accuracy percentage
     */
    public static function calculateAccuracy(int $correctCharacters, int $totalCharacters): float
    {
        if ($totalCharacters === 0) {
            return 0;
        }

        return round(($correctCharacters / $totalCharacters) * 100, 2);
    }

    /**
     * Compare typed text with original and return stats
     *
     * @param  string  $originalText  The text that should have been typed
     * @param  string  $typedText  The text that was actually typed
     * @return array Stats including total_characters (what was typed), correct, and incorrect
     */
    public static function analyzeTyping(string $originalText, string $typedText): array
    {
        $originalChars = str_split($originalText);
        $typedChars = str_split($typedText);

        $totalTypedCharacters = count($typedChars); // How many characters were actually typed
        $correctCharacters = 0;

        // Compare character by character
        for ($i = 0; $i < min(count($originalChars), count($typedChars)); $i++) {
            if ($originalChars[$i] === $typedChars[$i]) {
                $correctCharacters++;
            }
        }

        $incorrectCharacters = $totalTypedCharacters - $correctCharacters;

        return [
            'total_characters' => $totalTypedCharacters, // Changed: now returns typed length, not original length
            'correct_characters' => $correctCharacters,
            'incorrect_characters' => max(0, $incorrectCharacters),
        ];
    }
}
