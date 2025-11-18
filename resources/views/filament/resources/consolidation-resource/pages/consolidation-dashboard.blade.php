<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Consolidation Dashboard</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track and manage new members, VIPs, and first-timers.</p>
            </div>
            <div>
                <a href="{{ \App\Filament\Resources\ConsolidationResource::getUrl('create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 dark:bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add New Member
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Members</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->getTotalMembers() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Not Contacted</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->getNotContactedCount() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">In Progress</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->getInProgressCount() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->getCompletedCount() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-1 w-full">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by name or consolidator..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                <div class="flex gap-2">
                    <select
                        wire:model.live="statusFilter"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">All Statuses</option>
                        <option value="not_contacted">Not Contacted</option>
                        <option value="contacted">Contacted</option>
                        <option value="in_progress">In Progress</option>
                        <option value="follow_up_scheduled">Follow-up Scheduled</option>
                        <option value="completed">Completed</option>
                    </select>
                    <select
                        wire:model.live="attendanceStatusFilter"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">All Types</option>
                        <option value="1st">1st Timer</option>
                        <option value="2nd">2nd Timer</option>
                        <option value="3rd">3rd Timer</option>
                        <option value="4th">4th Timer</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">Showing {{ count($members) }} of {{ $this->getTotalMembers() }} members</p>
            </div>
        </div>

        <!-- Members Grid -->
        @if(!empty($members))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($members as $member)
                    @php
                        $attendanceStatusColors = [
                            '1st' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                            '2nd' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                            '3rd' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                            '4th' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                        ];
                        $consolidationStatusColors = [
                            'not_contacted' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            'contacted' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                            'in_progress' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                            'follow_up_scheduled' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                            'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                        ];
                        $consolidationStatusLabels = [
                            'not_contacted' => 'Not Contacted',
                            'contacted' => 'Contacted',
                            'in_progress' => 'In Progress',
                            'follow_up_scheduled' => 'Follow-up Scheduled',
                            'completed' => 'Completed',
                        ];
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $member['name'] }}</h3>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $attendanceStatusColors[$member['attendance_status']] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $member['attendance_status'] }} Timer
                                    </span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $consolidationStatusColors[$member['consolidation_status']] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $consolidationStatusLabels[$member['consolidation_status']] ?? ucfirst(str_replace('_', ' ', $member['consolidation_status'])) }}
                                    </span>
                                </div>
                            </div>
                            @if($member['consolidation_id'])
                                <a href="{{ \App\Filament\Resources\ConsolidationResource::getUrl('edit', ['record' => $member['consolidation_id']]) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </a>
                            @endif
                        </div>

                        <div class="space-y-2 mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Email:</span> {{ $member['email'] ?? 'No email' }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Consolidator:</span> {{ $member['consolidator_name'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                <span class="font-medium">Added:</span> {{ \Carbon\Carbon::parse($member['date'])->format('M d, Y') }}
                            </p>
                        </div>

                        @if(!empty($member['interest']))
                            <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">Interest:</span> {{ $member['interest'] }}
                                </p>
                            </div>
                        @endif

                        @if(!empty($member['next_action']))
                            <div class="mb-4">
                                <a href="{{ $member['consolidation_id'] ? \App\Filament\Resources\ConsolidationResource::getUrl('edit', ['record' => $member['consolidation_id']]) : '#' }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                                    {{ $member['next_action'] }}
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No members found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a new member.</p>
                <div class="mt-6">
                    <a href="{{ \App\Filament\Resources\ConsolidationResource::getUrl('create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                        Add New Member
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
