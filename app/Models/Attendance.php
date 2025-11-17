<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cell_group_id',
        'attendance_date',
        'attendance_type',
        'is_present',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'is_present' => 'boolean',
    ];

    /**
     * Get the user who attended.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cell group where attendance was recorded.
     */
    public function cellGroup()
    {
        return $this->belongsTo(CellGroup::class);
    }
}

