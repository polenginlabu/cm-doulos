<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\Models\Discipleship;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'attendance_status',
        'first_attendance_date',
        'last_attendance_date',
        'total_attendances',
        'cell_group_id',
        'notes',
        'invitation_token',
        'invited_at',
        'invited_by',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'first_attendance_date' => 'date',
            'last_attendance_date' => 'date',
            'invited_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the cell group that this user belongs to.
     */
    public function cellGroup()
    {
        return $this->belongsTo(CellGroup::class);
    }

    /**
     * Get all attendances for this user.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get all disciples (users mentored by this user).
     */
    public function disciples()
    {
        return $this->hasMany(Discipleship::class, 'mentor_id')
            ->where('status', 'active')
            ->with('disciple');
    }

    /**
     * Get all discipleships where this user is a mentor.
     */
    public function mentorships()
    {
        return $this->hasMany(Discipleship::class, 'mentor_id');
    }

    /**
     * Get the mentor (user who mentors this user).
     */
    public function mentor()
    {
        return $this->hasOne(Discipleship::class, 'disciple_id')
            ->where('status', 'active')
            ->with('mentor');
    }

    /**
     * Get all discipleships where this user is a disciple.
     */
    public function discipleships()
    {
        return $this->hasMany(Discipleship::class, 'disciple_id');
    }

    /**
     * Get the user who invited this user.
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get all users invited by this user.
     */
    public function invitedUsers()
    {
        return $this->hasMany(User::class, 'invited_by');
    }

    /**
     * Get cell groups led by this user.
     */
    public function ledCellGroups()
    {
        return $this->hasMany(CellGroup::class, 'leader_id');
    }

    /**
     * Get all users in this user's network (all disciples and their disciples recursively).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getNetworkUsers()
    {
        $networkIds = $this->getNetworkUserIds();
        return User::whereIn('id', $networkIds);
    }

    /**
     * Get all user IDs in this user's network recursively.
     *
     * @return array<int>
     */
    public function getNetworkUserIds(): array
    {
        $ids = [$this->id];

        // Get direct disciples
        $directDisciples = Discipleship::where('mentor_id', $this->id)
            ->where('status', 'active')
            ->pluck('disciple_id')
            ->toArray();

        $ids = array_merge($ids, $directDisciples);

        // Recursively get disciples of disciples
        foreach ($directDisciples as $discipleId) {
            $disciple = User::find($discipleId);
            if ($disciple) {
                $ids = array_merge($ids, $disciple->getNetworkUserIds());
            }
        }

        return array_unique($ids);
    }

    /**
     * Check if a user is in this user's network.
     */
    public function isInNetwork($userId)
    {
        return in_array($userId, $this->getNetworkUserIds());
    }
}
