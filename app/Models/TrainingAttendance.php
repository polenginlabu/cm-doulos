<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_enrollment_id',
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
        return $this->belongsTo(TrainingEnrollment::class, 'training_enrollment_id');
    }
}

