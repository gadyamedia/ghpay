<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date', 'end_date', 'pay_date', 'label', 'is_locked',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pay_date' => 'date',
        'is_locked' => 'boolean',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PayPeriodLog::class);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?? $this->start_date->format('M d').' – '.$this->end_date->format('M d, Y');
    }
}
