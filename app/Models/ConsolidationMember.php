<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsolidationMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consolidator_id',
        'status',
        'interest',
        'notes',
        'next_action',
        'added_at',
        'contacted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'date',
            'contacted_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    /**
     * Get the user this member is linked to (if any)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the consolidator assigned to this member
     */
    public function consolidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consolidator_id');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'first_timer' => 'First Timer',
            'second_timer' => 'Second Timer',
            'third_timer' => 'Third Timer',
            'fourth_timer' => 'Fourth Timer',
            'vip' => 'VIP',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'not_contacted' => 'Not Contacted',
            'contacted' => 'Contacted',
            'in_progress' => 'In Progress',
            'follow_up_scheduled' => 'Follow-up Scheduled',
            'completed' => 'Completed',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

}

