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
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'attendance_status',
        'first_attendance_date',
        'last_attendance_date',
        'total_attendances',
        'cell_group_id',
        'primary_user_id',
        'gender',
        'is_primary_leader',
        'is_super_admin',
        'is_network_admin',
        'is_equipping_admin',
        'category',
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
            'is_network_admin' => 'boolean',
            'is_equipping_admin' => 'boolean',
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
     * Get the network leader of this user (alias for primaryUser).
     */
    public function networkLeader()
    {
        return $this->primaryUser();
    }

    /**
     * Get the primary user of this user.
     */
    public function primaryUser()
    {
        return $this->belongsTo(User::class, 'primary_user_id');
    }

    /**
     * Get all users who have this user as their network leader (alias for primaryUserMembers).
     */
    public function networkLeaderMembers()
    {
        return $this->hasMany(User::class, 'primary_user_id');
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
     * Get all training enrollments for this user.
     */
    public function trainingEnrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /**
     * Calculate and update the user's category based on engagement.
     *
     * Categories:
     * C1: Engaged in all 4 (Sunday Service, Cell Group, Devotion, Training)
     * C2: Engaged in 3 activities
     * C3: Engaged in 2 activities
     * C4: Engaged in 1 or fewer activities
     */
    public function calculateCategory(): string
    {
        $engagements = 0;

        // Check Sunday Service engagement (has at least one attendance in last 30 days)
        $hasSundayService = $this->attendances()
            ->where('attendance_type', 'sunday_service')
            ->where('is_present', true)
            ->where('attendance_date', '>=', now()->subDays(30))
            ->exists();
        if ($hasSundayService) {
            $engagements++;
        }

        // Check Cell Group engagement (has cell_group_id OR has cell group attendance in last 30 days)
        $hasCellGroup = $this->cell_group_id !== null ||
            $this->attendances()
                ->where('attendance_type', 'cell_group')
                ->where('is_present', true)
                ->where('attendance_date', '>=', now()->subDays(30))
                ->exists();
        if ($hasCellGroup) {
            $engagements++;
        }

        // Check Devotion engagement
        // TODO: Implement devotion tracking when available
        // For now, we'll check if there's a devotion model/table
        // If devotion tracking exists, uncomment and implement:
        // $hasDevotion = $this->devotions()->where('devotion_date', '>=', now()->subDays(30))->exists();
        $hasDevotion = false; // Placeholder - set to true when devotion tracking is implemented
        if ($hasDevotion) {
            $engagements++;
        }

        // Check Training engagement (has active training enrollments)
        $hasTraining = $this->trainingEnrollments()
            ->where('status', 'active')
            ->exists();
        if ($hasTraining) {
            $engagements++;
        }

        // Determine category based on number of engagements
        // Note: Since devotion is not yet implemented, max engagements is 3 for now
        // When devotion is implemented, max will be 4
        if ($engagements >= 4) {
            return 'C1';
        } elseif ($engagements >= 3) {
            return 'C2';
        } elseif ($engagements >= 2) {
            return 'C3';
        } else {
            return 'C4';
        }
    }

    /**
     * Update the user's category based on current engagement.
     */
    public function updateCategory(): void
    {
        $this->category = $this->calculateCategory();
        $this->saveQuietly();
    }

    /**
     * Check if a user is in this user's network.
     */
    public function isInNetwork($userId)
    {
        return in_array($userId, $this->getNetworkUserIds());
    }

    /**
     * Get the user's full name.
     * Combines first_name and last_name.
     */
    public function getNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
