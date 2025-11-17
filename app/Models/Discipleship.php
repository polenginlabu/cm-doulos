<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Discipleship extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'disciple_id',
        'started_at',
        'ended_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($discipleship) {
            // Prevent self-mentorship
            if ($discipleship->mentor_id === $discipleship->disciple_id) {
                return false; // Prevent creation
            }
        });

        static::created(function ($discipleship) {
            // Clear family tree cache for all affected users
            static::clearFamilyTreeCache($discipleship->mentor_id, $discipleship->disciple_id);
        });

        static::updated(function ($discipleship) {
            // Clear family tree cache for all affected users
            static::clearFamilyTreeCache($discipleship->mentor_id, $discipleship->disciple_id);

            // Also clear cache for old values if they changed
            if ($discipleship->isDirty(['mentor_id', 'disciple_id', 'status'])) {
                $originalMentorId = $discipleship->getOriginal('mentor_id');
                $originalDiscipleId = $discipleship->getOriginal('disciple_id');
                if ($originalMentorId || $originalDiscipleId) {
                    static::clearFamilyTreeCache($originalMentorId, $originalDiscipleId);
                }
            }
        });

        static::deleted(function ($discipleship) {
            // Clear family tree cache for all affected users
            static::clearFamilyTreeCache($discipleship->mentor_id, $discipleship->disciple_id);
        });

        static::updating(function ($discipleship) {
            // Prevent self-mentorship
            if ($discipleship->mentor_id === $discipleship->disciple_id) {
                return false; // Prevent update
            }
        });
    }

    /**
     * Get the mentor (user who mentors).
     */
    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Get the disciple (user being mentored).
     */
    public function disciple()
    {
        return $this->belongsTo(User::class, 'disciple_id');
    }

    /**
     * Clear family tree cache for affected users
     */
    protected static function clearFamilyTreeCache($mentorId, $discipleId): void
    {
        // Clear cache for the mentor
        if ($mentorId) {
            Cache::forget("family_tree_{$mentorId}");
            Cache::forget("family_tree_stats_{$mentorId}");
        }

        // Clear cache for the disciple
        if ($discipleId) {
            Cache::forget("family_tree_{$discipleId}");
            Cache::forget("family_tree_stats_{$discipleId}");
        }

        // Clear cache for all users in the network (get all users who might be affected)
        // This is a simple approach - in production you might want to be more selective
        $allUserIds = \App\Models\User::pluck('id');
        foreach ($allUserIds as $userId) {
            Cache::forget("family_tree_{$userId}");
            Cache::forget("family_tree_stats_{$userId}");
        }
    }
}

