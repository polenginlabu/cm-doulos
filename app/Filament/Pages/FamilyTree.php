<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Discipleship;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FamilyTree extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.pages.family-tree';

    protected static ?string $navigationLabel = 'Family Tree';

    protected static ?string $title = 'Discipleship Network Tree';

    protected static ?int $navigationSort = 1;

    public array $networkData = [];
    public array $networkStats = [];

    public function mount(): void
    {
        $this->loadNetworkTree();
        $this->loadNetworkStats();
    }

    public function loadNetworkTree(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $this->networkData = $this->buildTreeOptimized($user);
    }

    public function refreshTree(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $cacheKey = "family_tree_{$user->id}";
        $statsCacheKey = "family_tree_stats_{$user->id}";

        // Clear cache
        Cache::forget($cacheKey);
        Cache::forget($statsCacheKey);

        // Reload
        $this->loadNetworkTree();
        $this->loadNetworkStats();

        $this->dispatch('tree-refreshed');
    }

    /**
     * Optimized tree building - loads all data in minimal queries
     * Since a disciple can only have ONE mentor, we can optimize this significantly
     */
    protected function buildTreeOptimized(User $user, $maxLevel = 5): array
    {
        // Load ALL active discipleships in ONE query
        $allDiscipleships = Discipleship::where('status', 'active')
            ->select('mentor_id', 'disciple_id')
            ->get()
            ->groupBy('mentor_id')
            ->map(function ($group) {
                return $group->pluck('disciple_id')->toArray();
            })
            ->toArray();

        // Get all user IDs in the network (using optimized method)
        $networkUserIds = $this->getNetworkUserIdsOptimized($user->id, $allDiscipleships);

        // Load all user data in ONE query
        $userMap = User::whereIn('id', $networkUserIds)
            ->select('id', 'first_name', 'last_name', 'email', 'attendance_status', 'total_attendances', 'is_primary_leader', 'is_network_admin', 'is_equipping_admin')
            ->get()
            ->keyBy('id');

        // Build tree structure from in-memory data
        return $this->buildTreeFromMap($user->id, $allDiscipleships, $userMap, 0, $maxLevel);
    }

    /**
     * Get all user IDs in network using breadth-first traversal
     * Since a disciple has only ONE mentor, we traverse down from mentor to disciples
     */
    protected function getNetworkUserIdsOptimized(int $rootUserId, array $allDiscipleships): array
    {
        $ids = [$rootUserId];
        $queue = [$rootUserId];
        $processed = [];

        while (!empty($queue)) {
            $currentId = array_shift($queue);

            if (isset($processed[$currentId])) {
                continue;
            }
            $processed[$currentId] = true;

            // Get all disciples of current user (mentor can have many disciples)
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
     * Build tree from pre-loaded data (no database queries)
     */
    protected function buildTreeFromMap(int $userId, array $allDiscipleships, $userMap, $level = 0, $maxLevel = 5): array
    {
        if ($level > $maxLevel) {
            return [];
        }

        // If user is not in map, try to load it (shouldn't happen, but safety check)
        if (!isset($userMap[$userId])) {
            $user = User::find($userId);
            if (!$user) {
                return [];
            }
            $userMap[$userId] = $user;
        }

        $user = $userMap[$userId];

        // Count total disciples recursively
        $discipleCount = $this->countTotalDisciples($userId, $allDiscipleships);

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'attendance_status' => $user->attendance_status,
            'total_attendances' => $user->total_attendances,
            'is_primary_leader' => $user->is_primary_leader,
            'is_network_admin' => $user->is_network_admin,
            'is_equipping_admin' => $user->is_equipping_admin,
            'level' => $level,
            'disciple_count' => $discipleCount,
            'children' => [],
        ];

        // Get disciples from pre-loaded data
        if (isset($allDiscipleships[$userId])) {
            foreach ($allDiscipleships[$userId] as $discipleId) {
                $childData = $this->buildTreeFromMap(
                    $discipleId,
                    $allDiscipleships,
                    $userMap,
                    $level + 1,
                    $maxLevel
                );
                // Only add if we got valid data back (not empty array)
                if (!empty($childData) && isset($childData['id'])) {
                    $data['children'][] = $childData;
                }
            }
        }

        return $data;
    }

    public function loadNetworkStats(): void
    {
        if (!Auth::check()) {
            $this->networkStats = [];
            return;
        }

        $user = Auth::user();
        $this->networkStats = $this->calculateNetworkStats($user);
    }

    protected function calculateNetworkStats(User $user): array
    {
        // Load all active discipleships in ONE query
        $allDiscipleships = Discipleship::where('status', 'active')
            ->select('mentor_id', 'disciple_id')
            ->get()
            ->groupBy('mentor_id')
            ->map(function ($group) {
                return $group->pluck('disciple_id')->toArray();
            })
            ->toArray();

        $networkIds = $this->getNetworkUserIdsOptimized($user->id, $allDiscipleships);

        $directDisciples = isset($allDiscipleships[$user->id])
            ? count($allDiscipleships[$user->id])
            : 0;

        $maxDepth = $this->getMaxDepthOptimized($user->id, $allDiscipleships);

        return [
            'total_members' => count($networkIds),
            'direct_disciples' => $directDisciples,
            'total_levels' => $maxDepth,
        ];
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

    /**
     * Optimized depth calculation using pre-loaded data
     */
    protected function getMaxDepthOptimized(int $userId, array $allDiscipleships, $currentDepth = 0, $maxDepth = 0, $visited = []): int
    {
        if (isset($visited[$userId])) {
            return $maxDepth; // Prevent infinite loops
        }
        $visited[$userId] = true;

        if (!isset($allDiscipleships[$userId]) || empty($allDiscipleships[$userId])) {
            return max($maxDepth, $currentDepth);
        }

        $maxDepth = max($maxDepth, $currentDepth + 1);

        foreach ($allDiscipleships[$userId] as $discipleId) {
            $maxDepth = max(
                $maxDepth,
                $this->getMaxDepthOptimized(
                    $discipleId,
                    $allDiscipleships,
                    $currentDepth + 1,
                    $maxDepth,
                    $visited
                )
            );
        }

        return $maxDepth;
    }

    /**
     * Get network stats (cached property accessor)
     */
    public function getNetworkStats(): array
    {
        return $this->networkStats;
    }
}

