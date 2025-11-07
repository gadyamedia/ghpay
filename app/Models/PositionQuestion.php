<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionQuestion extends Model
{
    protected $fillable = [
        'position_id',
        'question',
        'type',
        'options',
        'is_required',
        'order',
        'scoring_weight',
        'correct_answer',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'order' => 'integer',
            'scoring_weight' => 'integer',
            'correct_answer' => 'json',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function isMultipleChoice(): bool
    {
        return $this->type === 'multiple_choice';
    }

    public function isYesNo(): bool
    {
        return $this->type === 'yes_no';
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isTextarea(): bool
    {
        return $this->type === 'textarea';
    }
}
