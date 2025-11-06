<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id', 'label', 'rate', 'hours', 'amount',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}
