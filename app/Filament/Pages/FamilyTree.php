<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Discipleship;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class FamilyTree extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.pages.family-tree';

    protected static ?string $navigationLabel = 'Family Tree';

    protected static ?string $title = 'Discipleship Network Tree';

    protected static ?int $navigationSort = 1;

    public array $networkData = [];

    public function mount(): void
    {
        $this->loadNetworkTree();
    }

    public function loadNetworkTree(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $this->networkData = $this->buildTree($user);
    }

    protected function buildTree(User $user, $level = 0, $maxLevel = 5): array
    {
        if ($level > $maxLevel) {
            return [];
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'attendance_status' => $user->attendance_status,
            'total_attendances' => $user->total_attendances,
            'level' => $level,
            'children' => [],
        ];

        // Get direct disciples
        $discipleships = Discipleship::where('mentor_id', $user->id)
            ->where('status', 'active')
            ->with('disciple')
            ->get();

        foreach ($discipleships as $discipleship) {
            if ($discipleship->disciple) {
                $data['children'][] = $this->buildTree($discipleship->disciple, $level + 1, $maxLevel);
            }
        }

        return $data;
    }

    public function getNetworkStats(): array
    {
        if (!Auth::check()) {
            return [];
        }

        /** @var User $user */
        $user = Auth::user();
        if (!$user || !method_exists($user, 'getNetworkUserIds')) {
            return [];
        }
        $networkIds = $user->getNetworkUserIds();

        return [
            'total_members' => count($networkIds),
            'direct_disciples' => Discipleship::where('mentor_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'total_levels' => $this->getMaxDepth($user),
        ];
    }

    protected function getMaxDepth(User $user, $currentDepth = 0, $maxDepth = 0): int
    {
        $discipleships = Discipleship::where('mentor_id', $user->id)
            ->where('status', 'active')
            ->with('disciple')
            ->get();

        if ($discipleships->isEmpty()) {
            return $currentDepth;
        }

        $maxDepth = max($maxDepth, $currentDepth + 1);

        foreach ($discipleships as $discipleship) {
            if ($discipleship->disciple) {
                $maxDepth = max($maxDepth, $this->getMaxDepth($discipleship->disciple, $currentDepth + 1, $maxDepth));
            }
        }

        return $maxDepth;
    }
}

