<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'position_id',
        'candidate_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'location',
        'cover_letter',
        'resume_path',
        'portfolio_url',
        'linkedin_url',
        'github_url',
        'references',
        'screening_answers',
        'screening_score',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'ip_address',
        'user_agent',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'references' => 'array',
            'screening_answers' => 'array',
            'screening_score' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function calculateScreeningScore(): int
    {
        if (! $this->screening_answers) {
            return 0;
        }

        $score = 0;
        $questions = $this->position->questions()->whereNotNull('correct_answer')->get();

        foreach ($questions as $question) {
            $answer = $this->screening_answers[$question->id] ?? null;

            if ($answer !== null && $question->correct_answer !== null) {
                // Check if the answer matches the correct answer
                if (is_array($question->correct_answer)) {
                    if (in_array($answer, $question->correct_answer)) {
                        $score += $question->scoring_weight ?? 1;
                    }
                } elseif ($answer == $question->correct_answer) {
                    $score += $question->scoring_weight ?? 1;
                }
            }
        }

        return $score;
    }

    public function markAsReviewed(User $user): void
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);
    }

    public function updateStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    public function scopeByPosition($query, ?int $positionId)
    {
        return $positionId ? $query->where('position_id', $positionId) : $query;
    }

    public function scopeByStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeRecentFirst($query)
    {
        return $query->latest();
    }
}
