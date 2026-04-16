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
            // Prevent self-mentorship (cast to int to handle string/int from forms)
            if ((int) $discipleship->mentor_id === (int) $discipleship->disciple_id) {
                return false; // Prevent creation
            }

            // CRITICAL: If this is being set as active, deactivate all other active discipleships for this disciple
            // This ensures a disciple can only have ONE active mentor at a time
            if ($discipleship->status === 'active') {
                Discipleship::where('disciple_id', $discipleship->disciple_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $discipleship->id ?? 0) // Exclude current record if it exists
                    ->update(['status' => 'inactive']);
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
            // Prevent self-mentorship (cast to int to handle string/int from forms)
            if ((int) $discipleship->mentor_id === (int) $discipleship->disciple_id) {
                return false; // Prevent update
            }

            // CRITICAL: If this is being set as active, deactivate all other active discipleships for this disciple
            // This ensures a disciple can only have ONE active mentor at a time
            if ($discipleship->isDirty('status') && $discipleship->status === 'active') {
                Discipleship::where('disciple_id', $discipleship->disciple_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $discipleship->id) // Exclude current record
                    ->update(['status' => 'inactive']);
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
     * Clear family tree cache for affected users.
     *
     * Walks UP the mentor chain from both the mentor and disciple to clear
     * caches of all ancestors who include these users in their tree view.
     */
    protected static function clearFamilyTreeCache($mentorId, $discipleId): void
    {
        $idsToInvalidate = collect();

        // Collect the mentor + all ancestors up the chain
        if ($mentorId) {
            $idsToInvalidate->push($mentorId);
            static::collectAncestorIds($mentorId, $idsToInvalidate);
        }

        // Collect the disciple + all ancestors up the chain
        if ($discipleId) {
            $idsToInvalidate->push($discipleId);
            static::collectAncestorIds($discipleId, $idsToInvalidate);
        }

        foreach ($idsToInvalidate->unique() as $userId) {
            Cache::forget("family_tree_{$userId}");
            Cache::forget("family_tree_stats_{$userId}");
        }
    }

    /**
     * Walk up the mentor chain and collect ancestor IDs.
     */
    protected static function collectAncestorIds(int $userId, \Illuminate\Support\Collection $ids, int $depth = 0): void
    {
        if ($depth >= 50) {
            return;
        }

        $mentorId = static::where('disciple_id', $userId)
            ->where('status', 'active')
            ->value('mentor_id');

        if ($mentorId && !$ids->contains($mentorId)) {
            $ids->push($mentorId);
            static::collectAncestorIds($mentorId, $ids, $depth + 1);
        }
    }
}

