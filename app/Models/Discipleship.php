<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
            if ($discipleship->mentor_id === $discipleship->disciple_id) {
                throw new \InvalidArgumentException('A user cannot be their own disciple.');
            }
        });

        static::updating(function ($discipleship) {
            if ($discipleship->mentor_id === $discipleship->disciple_id) {
                throw new \InvalidArgumentException('A user cannot be their own disciple.');
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
}

