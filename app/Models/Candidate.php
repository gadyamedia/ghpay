<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'position_applied',
        'status',
        'notes',
        'created_by',
        'invited_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typingTests(): HasMany
    {
        return $this->hasMany(TypingTest::class);
    }

    public function latestTypingTest(): HasOne
    {
        return $this->hasOne(TypingTest::class)->latestOfMany();
    }

    public function testInvitations(): HasMany
    {
        return $this->hasMany(TestInvitation::class);
    }

    public function activeInvitation(): HasOne
    {
        return $this->hasOne(TestInvitation::class)
            ->where('expires_at', '>', now())
            ->whereNull('completed_at')
            ->latestOfMany();
    }

    public function getBestWpmAttribute(): ?int
    {
        return $this->typingTests()->max('wpm');
    }

    public function getAverageAccuracyAttribute(): ?float
    {
        return $this->typingTests()->avg('accuracy');
    }
}
