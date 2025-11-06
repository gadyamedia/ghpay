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
     * Standard: 1 word = 5 characters
     * Formula: Total Number of Words = Total Keys Pressed / 5
     *          WPM = Total Number of Words / Time Elapsed in Minutes (rounded down)
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
     */
    public static function analyzeTyping(string $originalText, string $typedText): array
    {
        $originalChars = str_split($originalText);
        $typedChars = str_split($typedText);

        $totalCharacters = count($originalChars);
        $correctCharacters = 0;

        for ($i = 0; $i < min(count($originalChars), count($typedChars)); $i++) {
            if ($originalChars[$i] === $typedChars[$i]) {
                $correctCharacters++;
            }
        }

        $incorrectCharacters = $totalCharacters - $correctCharacters;

        return [
            'total_characters' => $totalCharacters,
            'correct_characters' => $correctCharacters,
            'incorrect_characters' => max(0, $incorrectCharacters),
        ];
    }
}
