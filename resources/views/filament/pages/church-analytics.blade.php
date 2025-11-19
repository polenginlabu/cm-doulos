@php
    $summary = $this->summary ?? [];
    $attendanceBreakdown = $this->attendanceBreakdown ?? ['labels' => [], 'totals' => []];
    $attendanceTrend = $this->attendanceTrend ?? ['labels' => [], 'totals' => []];
@endphp

<x-filament::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    Church Analytics Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Track attendance trends and first-timer engagement across your network.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-500">View</span>
                <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                    Weekly View
                </span>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {{-- Average Attendance --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Average Attendance
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ $summary['averageAttendance'] ?? 0 }}
                        </p>
                        <p class="mt-1 text-xs text-emerald-600">
                            vs last {{ $summary['monthsCount'] ?? 6 }} months
                        </p>
                    </div>
                    <x-heroicon-o-users class="h-8 w-8 text-gray-300" />
                </div>
            </div>

            {{-- Total Members (unique attendees in the period) --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Total Members Attended
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ $summary['totalMembers'] ?? 0 }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Unique people who attended in the last {{ $summary['monthsCount'] ?? 6 }} months
                        </p>
                    </div>
                    <x-heroicon-o-user-plus class="h-8 w-8 text-gray-300" />
                </div>
            </div>

            {{-- Growth Rate --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Growth Rate
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ $summary['growthRate'] ?? 0 }}%
                        </p>
                        <p class="mt-1 flex items-center text-xs {{ ($summary['growthIsPositive'] ?? true) ? 'text-emerald-600' : 'text-rose-600' }}">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-50">
                                @if(($summary['growthIsPositive'] ?? true))
                                    <x-heroicon-o-arrow-trending-up class="h-3 w-3" />
                                @else
                                    <x-heroicon-o-arrow-trending-down class="h-3 w-3" />
                                @endif
                            </span>
                            <span class="ml-1">
                                Trend is {{ ($summary['growthIsPositive'] ?? true) ? 'positive' : 'negative' }}
                            </span>
                        </p>
                    </div>
                    <x-heroicon-o-chart-bar class="h-8 w-8 text-gray-300" />
                </div>
            </div>

            {{-- Total Attendance --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Total Attendance
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">
                            {{ $summary['totalAttendance'] ?? 0 }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Across {{ $summary['monthsCount'] ?? 6 }} months
                        </p>
                    </div>
                    <x-heroicon-o-calendar-days class="h-8 w-8 text-gray-300" />
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex space-x-2 rounded-xl bg-gray-100 p-1 text-sm font-medium">
            <button
                type="button"
                class="flex-1 rounded-lg bg-white px-3 py-2 text-center text-gray-900 shadow-sm"
            >
                Attendance Trends
            </button>
            <button
                type="button"
                class="flex-1 rounded-lg px-3 py-2 text-center text-gray-500 hover:text-gray-700"
            >
                First Timers
            </button>
        </div>

        {{-- Charts --}}
        <div class="grid gap-6 lg:grid-cols-1">
            {{-- Attendance Breakdown --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Monthly Attendance Overview
                        </h3>
                        <p class="text-xs text-gray-500">
                            Track your church attendance patterns and growth trends.
                        </p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="h-64">
                        <canvas id="attendance-breakdown-chart"></canvas>
                    </div>

                    <div class="h-56 border-t border-gray-100 pt-4">
                        <h4 class="mb-2 text-sm font-semibold text-gray-900">
                            Total Attendance Trend
                        </h4>
                        {{-- Trend chart temporarily disabled to avoid widget errors on this custom page. --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const breakdownCtx = document.getElementById('attendance-breakdown-chart')?.getContext('2d');

                if (!breakdownCtx) return;

                // Ensure we don't create unlimited chart instances on Livewire re-renders.
                window.churchAnalyticsCharts = window.churchAnalyticsCharts || {};

                const breakdownData = @json($attendanceBreakdown);

                // Bar chart: Total Attendance per month (all members)
                if (window.churchAnalyticsCharts.breakdown) {
                    window.churchAnalyticsCharts.breakdown.destroy();
                }

                window.churchAnalyticsCharts.breakdown = new Chart(breakdownCtx, {
                    type: 'bar',
                    data: {
                        labels: breakdownData.labels ?? [],
                        datasets: [
                            {
                                label: 'Total Attendance',
                                data: (breakdownData.totals ?? []).slice(0, (breakdownData.labels ?? []).length),
                                backgroundColor: '#3b82f6',
                                borderRadius: 4,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            });
        </script>
    @endpush
</x-filament::page>


