<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'user_id', 'pay_period_id',
        'user_snapshot', 'gross_earnings', 'total_deductions', 'net_salary',
        'late_hours', 'absence_days', 'late_deduction', 'absence_deduction', 'override_deductions',
        'status', 'sent_at', 'acknowledged_at', 'acknowledged_by',
    ];

    protected $casts = [
        'user_snapshot' => 'array',
        'late_hours' => 'decimal:2',
        'absence_days' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'override_deductions' => 'boolean',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    /** Relationships */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(PayslipEarning::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayslipDeduction::class);
    }

    /** Computations */
    public function computeLatesAndAbsences(): void
    {
        $rate = optional($this->user->detail)->hourly_rate ?? 0;
        $dailyRate = $rate * 8;

        if (! $this->override_deductions) {
            $this->late_deduction = $this->late_hours * $rate;
            $this->absence_deduction = $this->absence_days * $dailyRate;
        }

        $this->recalculateTotals();
    }

    public function recalculateTotals(): void
    {
        $this->gross_earnings = $this->earnings->sum('amount');
        $this->total_deductions = $this->deductions->sum('amount')
            + $this->late_deduction
            + $this->absence_deduction;

        $this->net_salary = $this->gross_earnings - $this->total_deductions;
    }
}
