<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'custom_label',
        'week_start_date',
        'day_of_week',
        'position',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'day_of_week' => 'integer',
        'position' => 'integer',
    ];

    /**
     * Get the user for this itinerary item.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the activity for this itinerary item.
     */
    public function activity()
    {
        return $this->belongsTo(ItineraryActivity::class, 'activity_id');
    }
}
