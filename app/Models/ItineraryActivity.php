<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'user_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category this activity belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ItineraryCategory::class, 'category_id');
    }

    /**
     * Get the user who created this activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get itinerary items for this activity.
     */
    public function items()
    {
        return $this->hasMany(ItineraryItem::class, 'activity_id');
    }
}
