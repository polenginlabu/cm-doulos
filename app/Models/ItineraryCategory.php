<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get activities under this category.
     */
    public function activities()
    {
        return $this->hasMany(ItineraryActivity::class, 'category_id');
    }
}
