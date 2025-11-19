<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ChurchAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Church Analytics';

    protected static ?string $title = 'Church Analytics Dashboard';

    protected static ?string $navigationGroup = 'Attendance';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.church-analytics';

    /**
     * Summary cards data.
     */
    public array $summary = [];

    /**
     * Weekly attendance / first-timer breakdown data for bar chart.
     */
    public array $attendanceBreakdown = [];

    /**
     * Weekly total attendance data for line chart.
     */
    public array $attendanceTrend = [];

    /**
     * Current view mode (only weekly for now, but we keep it extensible).
     */
    public string $viewMode = 'weekly';

    public function mount(): void
    {
        $this->loadAnalytics();
    }

    /**
     * Load all analytics data for the dashboard.
     */
    protected function loadAnalytics(): void
    {
        // Determine the monthly window (last 6 months including current month).
        $monthsCount = 6;
        $endOfMonth = Carbon::now()->endOfMonth();
        $startOfWindow = (clone $endOfMonth)->subMonths($monthsCount - 1)->startOfMonth();

        $months = [];
        $monthLabels = [];

        for ($i = 0; $i < $monthsCount; $i++) {
            $monthStart = (clone $startOfWindow)->addMonths($i);
            $monthEnd = (clone $monthStart)->endOfMonth();

            $months[] = [
                'start' => $monthStart,
                'end' => $monthEnd,
            ];

            $monthLabels[] = $monthStart->format('M');
        }

        $filteredUserIds = $this->getFilteredUserIds();

        if (empty($filteredUserIds)) {
            // No users in network → return empty data.
            $this->summary = [
                'averageAttendance' => 0,
                'totalMembers' => 0,
                'growthRate' => 0,
                'growthIsPositive' => true,
                'totalAttendance' => 0,
                'monthsCount' => count($months),
            ];

            $this->attendanceBreakdown = [
                'labels' => $monthLabels,
                'totals' => array_fill(0, count($months), 0),
            ];

            $this->attendanceTrend = [
                'labels' => $monthLabels,
                'totals' => array_fill(0, count($months), 0),
            ];

            return;
        }

        // Fetch all sunday_service attendances for this window and network.
        $attendances = Attendance::query()
            ->where('attendance_type', 'sunday_service')
            ->whereBetween('attendance_date', [$startOfWindow->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('user_id', $filteredUserIds)
            ->where('is_present', true)
            ->get([
                'user_id',
                'attendance_date',
            ]);

        $totalPerMonth = array_fill(0, count($months), 0);

        // Map attendances to months (all members).
        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->attendance_date);

            foreach ($months as $index => $month) {
                if ($date->betweenIncluded($month['start'], $month['end'])) {
                    $totalPerMonth[$index]++;
                    break;
                }
            }
        }

        $totalAttendance = array_sum($totalPerMonth);
        $averageAttendance = count($months) > 0 ? round($totalAttendance / count($months)) : 0;
        $uniqueMembers = $attendances->pluck('user_id')->unique()->count();

        // Growth rate: compare last half months vs previous half months.
        $half = intdiv($monthsCount, 2);
        $firstPeriod = array_slice($totalPerMonth, 0, $half);
        $secondPeriod = array_slice($totalPerMonth, $half, $half);

        $firstAvg = !empty($firstPeriod) ? array_sum($firstPeriod) / count($firstPeriod) : 0;
        $secondAvg = !empty($secondPeriod) ? array_sum($secondPeriod) / count($secondPeriod) : 0;

        if ($firstAvg > 0) {
            $growthRate = round((($secondAvg - $firstAvg) / $firstAvg) * 100, 1);
        } else {
            $growthRate = $secondAvg > 0 ? 100.0 : 0.0;
        }

        $this->summary = [
            'averageAttendance' => $averageAttendance,
            'totalMembers' => $uniqueMembers,
            'growthRate' => $growthRate,
            'growthIsPositive' => $growthRate >= 0,
            'totalAttendance' => $totalAttendance,
            'monthsCount' => count($months),
        ];

        $this->attendanceBreakdown = [
            'labels' => $monthLabels,
            'totals' => $totalPerMonth,
        ];

        $this->attendanceTrend = [
            'labels' => $monthLabels,
            'totals' => $totalPerMonth,
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


