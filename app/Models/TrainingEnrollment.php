<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'batch_id',
        'user_id',
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
     * Get the training this enrollment belongs to
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the batch this enrollment belongs to
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class);
    }

    /**
     * Get the user this enrollment belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all attendances for this enrollment
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class);
    }

    /**
     * Get the number of lessons attended
     */
    public function getLessonsAttendedAttribute(): int
    {
        return $this->attendances()->where('is_present', true)->count();
    }

    /**
     * Get the progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if (!$this->training || $this->training->total_lessons == 0) {
            return 0;
        }

        return ($this->lessons_attended / $this->training->total_lessons) * 100;
    }
}

