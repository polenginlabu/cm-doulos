<?php

namespace App\Filament\Resources\NetworkOverviewResource\Pages;

use App\Filament\Resources\NetworkOverviewResource;
use App\Models\User;
use App\Models\Discipleship;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class NetworkTree extends Page
{
    protected static string $resource = NetworkOverviewResource::class;

    protected static string $view = 'filament.resources.network-overview-resource.pages.network-tree';

    protected static ?string $title = 'Network Overview';

    protected static ?string $navigationLabel = 'Network Overview';

    public function getDisciples(int $userId): array
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();

        // Only super admins and network admins can access
        if (!$user->is_super_admin && !$user->is_network_admin) {
            return [];
        }

        // Get the user
        $targetUser = User::find($userId);
        if (!$targetUser) {
            return [];
        }

        // Get all active discipleships
        $allDiscipleships = Discipleship::where('status', 'active')
            ->select('mentor_id', 'disciple_id')
            ->get()
            ->groupBy('mentor_id')
            ->map(function ($group) {
                return $group->pluck('disciple_id')->toArray();
            })
            ->toArray();

        // Get direct disciples
        $discipleIds = $allDiscipleships[$userId] ?? [];

        if (empty($discipleIds)) {
            return [];
        }

        // Load disciple data
        $disciples = User::whereIn('id', $discipleIds)
            ->select('id', 'first_name', 'last_name', 'email', 'attendance_status', 'total_attendances', 'is_primary_leader', 'is_network_admin', 'is_equipping_admin')
            ->get();

        $result = [];

        foreach ($disciples as $disciple) {
            // Count total disciples recursively
            $discipleCount = $this->countTotalDisciples($disciple->id, $allDiscipleships);

            // Check if this disciple has children
            $hasChildren = isset($allDiscipleships[$disciple->id]) && !empty($allDiscipleships[$disciple->id]);

            $result[] = [
                'id' => $disciple->id,
                'name' => $disciple->name,
                'email' => $disciple->email,
                'attendance_status' => $disciple->attendance_status,
                'total_attendances' => $disciple->total_attendances,
                'is_primary_leader' => $disciple->is_primary_leader,
                'is_network_admin' => $disciple->is_network_admin,
                'is_equipping_admin' => $disciple->is_equipping_admin,
                'disciple_count' => $discipleCount,
                'has_children' => $hasChildren,
            ];
        }

        return $result;
    }

    /**
     * Count total disciples recursively
     */
    protected function countTotalDisciples(int $userId, array $allDiscipleships): int
    {
        $count = 0;
        $queue = [$userId];
        $processed = [];

        while (!empty($queue)) {
            $currentId = array_shift($queue);

            if (isset($processed[$currentId])) {
                continue;
            }
            $processed[$currentId] = true;

            if (isset($allDiscipleships[$currentId])) {
                foreach ($allDiscipleships[$currentId] as $discipleId) {
                    if (!isset($processed[$discipleId])) {
                        $count++;
                        $queue[] = $discipleId;
                    }
                }
            }
        }

        return $count;
    }

    public function getPrimaryUser(): ?User
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // Only super admins and network admins can access
        if (!$user->is_super_admin && !$user->is_network_admin) {
            return null;
        }

        // Get the primary user (first primary leader)
        $primaryUser = User::where('is_primary_leader', true)
            ->orderBy('id')
            ->first();

        if ($primaryUser) {
            // Calculate disciple count for primary user
            $allDiscipleships = Discipleship::where('status', 'active')
                ->select('mentor_id', 'disciple_id')
                ->get()
                ->groupBy('mentor_id')
                ->map(function ($group) {
                    return $group->pluck('disciple_id')->toArray();
                })
                ->toArray();

            $primaryUser->disciple_count = $this->countTotalDisciples($primaryUser->id, $allDiscipleships);

            // Check if primary user has children
            $primaryUser->has_children = isset($allDiscipleships[$primaryUser->id]) && !empty($allDiscipleships[$primaryUser->id]);
        }

        return $primaryUser;
    }
}

