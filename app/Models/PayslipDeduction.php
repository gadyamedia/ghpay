<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id', 'label', 'amount',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}
