<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Attendance Dashboard</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Track weekly attendance for all team members</p>
            </div>
        </div>

        <!-- Week Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-center gap-4">
                <button type="button"
                        wire:click="previousWeek"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>

                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-lg font-semibold text-gray-900 dark:text-white">
                        @if($selectedDate)
                            {{ \Carbon\Carbon::parse($selectedDate)->startOfWeek()->format('M d') }} - {{ \Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}
                        @endif
                    </span>
                </div>

                <button type="button"
                        wire:click="nextWeek"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4 text-center">
                <button type="button"
                        wire:click="goToCurrentWeek"
                        class="px-4 py-2 bg-primary-600 dark:bg-primary-600 text-white dark:text-white rounded-lg text-sm font-medium hover:bg-primary-700 dark:hover:bg-primary-700 transition">
                    Current Week
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        @if($this->getTableQuery()->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Members</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $attendanceSummary['total_members'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Present</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ $attendanceSummary['sunday_service'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Absent</p>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">{{ ($attendanceSummary['total_members'] ?? 0) - ($attendanceSummary['sunday_service'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Members List with Filament Table -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sunday Service Attendance</h3>
            {{ $this->table }}
        </div>
        @endif
    </div>
</x-filament-panels::page>
