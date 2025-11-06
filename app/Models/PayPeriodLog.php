<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayPeriodLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_period_id', 'user_id', 'action', 'note',
    ];

    public function payPeriod(): BelongsTo
    {
        return $this->belongsTo(PayPeriod::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a log entry for a pay period action
     */
    public static function logAction(
        PayPeriod $payPeriod,
        string $action,
        ?string $note = null,
        ?string $userId = null
    ): self {
        return self::create([
            'pay_period_id' => $payPeriod->id,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'note' => $note,
        ]);
    }
}
