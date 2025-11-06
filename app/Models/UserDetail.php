<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Yajra\Address\HasAddress;

class UserDetail extends Model
{
    use HasAddress;

    protected $fillable = [
        'user_id',
        'hourly_rate',
        'position',
        'gender',
        'civil_status',
        'nationality',
        'hire_date',
        'birthday',
        'pagibig',
        'sss',
        'tin',
        'philhealth',
        'street',
        'barangay_id',
        'city_id',
        'province_id',
        'region_id',
    ];

    protected $casts = [
        'hire_date' => 'date:m/d/Y',
        'birthday' => 'date:m/d/Y',
        'hourly_rate' => 'decimal:2',
        'pagibig' => 'encrypted',
        'sss' => 'encrypted',
        'tin' => 'encrypted',
        'philhealth' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
