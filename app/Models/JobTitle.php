<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTitle extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['name', 'daily_rate', 'is_active'];

    protected $casts = ['daily_rate' => 'decimal:2', 'is_active' => 'boolean'];

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }
}


