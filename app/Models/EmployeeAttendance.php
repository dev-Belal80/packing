<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAttendance extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $table = 'employee_attendance';
    protected $fillable = [
        'packhouse_id', 'employee_name', 'job_title_id', 'attendance_date',
        'check_in', 'hours_worked', 'calculated_wage', 'is_present', 'absence_reason',
    ];
    protected $casts = [
        'attendance_date' => 'date',
        'is_present' => 'bool',
        'hours_worked' => 'float',
        'calculated_wage' => 'float',
    ];

    public function packhouse(): BelongsTo
    {
        return $this->belongsTo(Packhouse::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }
}

