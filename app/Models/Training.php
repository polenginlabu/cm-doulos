<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'total_lessons',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all enrollments for this training
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /**
     * Get active enrollments
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class)->where('status', 'enrolled');
    }

    /**
     * Get completed enrollments
     */
    public function completedEnrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class)->where('status', 'completed');
    }

    /**
     * Get all batches for this training
     */
    public function batches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class);
    }

    /**
     * Get active batches
     */
    public function activeBatches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class)->where('is_active', true);
    }
}

