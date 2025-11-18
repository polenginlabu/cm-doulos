<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'name',
        'code',
        'start_date',
        'end_date',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the training this batch belongs to
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get all enrollments for this batch
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class, 'batch_id');
    }

    /**
     * Get active enrollments
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class, 'batch_id')->where('status', 'enrolled');
    }

    /**
     * Get display name (training name + batch name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->training->name . ' - ' . $this->name;
    }
}

