<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public $timestamps = true;

    /**
     * Return users for this role
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Return normalized role name
     */
    public function getNameAttribute($value): string
    {
        return Str::lower($value);
    }
}
