<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuynlEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leader_id',
        'enrolled_at',
        'status',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    /**
     * Get the student/user this enrollment belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the leader this enrollment belongs to
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Get all attendances for this enrollment
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(SuynlAttendance::class);
    }

    /**
     * Get the number of lessons attended
     */
    public function getLessonsAttendedAttribute(): int
    {
        return $this->attendances()->where('is_present', true)->count();
    }

    /**
     * Get the progress percentage (out of 10 lessons)
     */
    public function getProgressPercentageAttribute(): float
    {
        $totalLessons = 10;
        if ($totalLessons == 0) {
            return 0;
        }
        return ($this->lessons_attended / $totalLessons) * 100;
    }
}

