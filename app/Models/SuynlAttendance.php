<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuynlAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'suynl_enrollment_id',
        'lesson_number',
        'attendance_date',
        'is_present',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'is_present' => 'boolean',
        ];
    }

    /**
     * Get the enrollment this attendance belongs to
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(SuynlEnrollment::class, 'suynl_enrollment_id');
    }
}

