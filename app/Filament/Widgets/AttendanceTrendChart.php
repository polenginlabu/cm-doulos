<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Total Attendance Trend';

    protected static ?string $maxHeight = '250px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // Determine the 8-week window (last 8 weeks including current week).
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfWindow = (clone $endOfWeek)->subWeeks(7)->startOfWeek();

        $weeks = [];
        $weekLabels = [];

        for ($i = 0; $i < 8; $i++) {
            $weekStart = (clone $startOfWindow)->addWeeks($i);
            $weekEnd = (clone $weekStart)->endOfWeek();

            $weeks[] = [
                'start' => $weekStart,
                'end' => $weekEnd,
            ];

            $weekLabels[] = 'Week ' . ($i + 1);
        }

        $filteredUserIds = $this->getFilteredUserIds();

        if (empty($filteredUserIds)) {
            return [
                'labels' => $weekLabels,
                'datasets' => [
                    [
                        'label' => 'Total Attendance',
                        'data' => array_fill(0, count($weeks), 0),
                        'borderColor' => '#6366f1',
                        'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                        'tension' => 0.3,
                        'fill' => true,
                    ],
                ],
            ];
        }

        $attendances = Attendance::query()
            ->where('attendance_type', 'sunday_service')
            ->whereBetween('attendance_date', [$startOfWindow->toDateString(), $endOfWeek->toDateString()])
            ->whereIn('user_id', $filteredUserIds)
            ->where('is_present', true)
            ->get(['attendance_date']);

        $totalPerWeek = array_fill(0, count($weeks), 0);

        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->attendance_date);
            foreach ($weeks as $index => $week) {
                if ($date->betweenIncluded($week['start'], $week['end'])) {
                    $totalPerWeek[$index]++;
                    break;
                }
            }
        }

        return [
            'labels' => $weekLabels,
            'datasets' => [
                [
                    'label' => 'Total Attendance',
                    'data' => $totalPerWeek,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
        ];
    }

    /**
     * Get filtered user IDs based on network and gender.
     */
    protected function getFilteredUserIds(): array
    {
        if (!Auth::check()) {
            return [];
        }

        /** @var User $user */
        $user = Auth::user();

        // Super admins and network admins can see all users.
        if ($user->is_super_admin || $user->is_network_admin) {
            $query = User::query();
        } else {
            // Regular users can only see their network.
            if (!method_exists($user, 'getNetworkUserIds')) {
                return [$user->id];
            }

            $networkIds = $user->getNetworkUserIds();
            $query = User::whereIn('id', $networkIds);
        }

        // Filter by gender (same gender only).
        if ($user->gender) {
            $query->where('gender', $user->gender);
        }

        return $query->pluck('id')->toArray();
    }
}

