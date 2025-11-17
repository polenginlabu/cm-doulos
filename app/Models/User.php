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
        'network_leader_id',
        'primary_user_id',
        'gender',
        'is_primary_leader',
        'is_super_admin',
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
            'is_primary_leader' => 'boolean',
            'is_super_admin' => 'boolean',
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
     * Get the network leader of this user.
     */
    public function networkLeader()
    {
        return $this->belongsTo(User::class, 'network_leader_id');
    }

    /**
     * Get the primary user of this user.
     */
    public function primaryUser()
    {
        return $this->belongsTo(User::class, 'primary_user_id');
    }

    /**
     * Get all users who have this user as their network leader.
     */
    public function networkLeaderMembers()
    {
        return $this->hasMany(User::class, 'network_leader_id');
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
     * Optimized to avoid N+1 queries by loading all discipleships at once.
     *
     * @return array<int>
     */
    public function getNetworkUserIds(): array
    {
        // Load all active discipleships in one query
        $allDiscipleships = Discipleship::where('status', 'active')
            ->select('mentor_id', 'disciple_id')
            ->get()
            ->groupBy('mentor_id')
            ->map(function ($group) {
                return $group->pluck('disciple_id')->toArray();
            })
            ->toArray();

        $ids = [$this->id];
        $queue = [$this->id];
        $processed = [];

        // Breadth-first traversal to collect all disciple IDs
        while (!empty($queue)) {
            $currentId = array_shift($queue);

            if (isset($processed[$currentId])) {
                continue;
            }
            $processed[$currentId] = true;

            // Get all disciples of current user
            if (isset($allDiscipleships[$currentId])) {
                foreach ($allDiscipleships[$currentId] as $discipleId) {
                    if (!isset($processed[$discipleId])) {
                        $ids[] = $discipleId;
                        $queue[] = $discipleId;
                    }
                }
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
