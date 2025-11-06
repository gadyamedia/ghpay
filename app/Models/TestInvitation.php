<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TestInvitation extends Model
{
    protected $fillable = [
        'candidate_id',
        'token',
        'expires_at',
        'opened_at',
        'completed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Generate a new invitation
     */
    public static function createForCandidate(Candidate $candidate, int $expiresInHours = 72): self
    {
        return self::create([
            'candidate_id' => $candidate->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }

    /**
     * Check if invitation is valid
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture() && is_null($this->completed_at);
    }

    /**
     * Check if invitation has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark invitation as opened
     */
    public function markAsOpened(?string $ipAddress = null, ?string $userAgent = null): void
    {
        if (is_null($this->opened_at)) {
            $this->update([
                'opened_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }
    }

    /**
     * Mark invitation as completed
     */
    public function markAsCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }
}
