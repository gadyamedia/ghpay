<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypingTextSample extends Model
{
    protected $fillable = [
        'title',
        'content',
        'difficulty',
        'word_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'word_count' => 'integer',
    ];

    public function typingTests(): HasMany
    {
        return $this->hasMany(TypingTest::class);
    }

    /**
     * Get a random active sample
     */
    public static function getRandomActive(?string $difficulty = null)
    {
        $query = self::where('is_active', true);

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        return $query->inRandomOrder()->first();
    }

    /**
     * Get all active samples for a difficulty
     */
    public static function getActiveByDifficulty(string $difficulty)
    {
        return self::where('is_active', true)
            ->where('difficulty', $difficulty)
            ->get();
    }

    /**
     * Calculate word count from content
     */
    public function calculateWordCount(): int
    {
        return str_word_count($this->content);
    }

    /**
     * Auto-update word count before saving
     */
    protected static function booted(): void
    {
        static::saving(function ($sample) {
            if ($sample->isDirty('content')) {
                $sample->word_count = $sample->calculateWordCount();
            }
        });
    }
}
