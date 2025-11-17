<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CellGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'leader_id',
        'parent_cell_group_id',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the leader of this cell group.
     */
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Get the parent cell group.
     */
    public function parentCellGroup()
    {
        return $this->belongsTo(CellGroup::class, 'parent_cell_group_id');
    }

    /**
     * Get all child cell groups.
     */
    public function childCellGroups()
    {
        return $this->hasMany(CellGroup::class, 'parent_cell_group_id');
    }

    /**
     * Get all members in this cell group.
     */
    public function members()
    {
        return $this->hasMany(User::class, 'cell_group_id');
    }

    /**
     * Get all attendances for this cell group.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}

