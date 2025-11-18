<x-filament-panels::page>
    <div class="space-y-6">
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Students</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->getTotalStudents() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Training</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->getCompletedCount() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Progress</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->getAverageProgress(), 0) }}%</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Students</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->getActiveCount() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Progress Tracker -->
        <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Student Progress Tracker</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track attendance for all 10 lessons.</p>
                </div>
            </div>

            <div class="space-y-6">
                @foreach($enrollments as $enrollment)
                    @php
                        $user = \App\Models\User::find($enrollment['user_id']);
                        $lessonsAttended = $enrollment['lessons_attended'] ?? 0;
                        $progress = ($lessonsAttended / 10) * 100;
                        $isCompleted = $lessonsAttended >= 10;
                        $lessonTitles = $this->getLessonTitles();
                        $attendedLessons = collect($enrollment['attendances'] ?? [])
                            ->where('is_present', true)
                            ->pluck('lesson_number')
                            ->toArray();
                    @endphp

                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-white dark:bg-gray-800/50 shadow-sm">
                        <!-- Student Info -->
                        <div class="mb-5">
                            <div class="flex items-center gap-3 mb-2">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $user->name ?? 'Unknown' }}</h4>
                                @if($isCompleted)
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                        Completed
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 ml-8">{{ $user->email ?? '' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 ml-8 mt-1">Joined: {{ \Carbon\Carbon::parse($enrollment['enrolled_at'])->format('M d, Y') }}</p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-5">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $lessonsAttended }} / 10 lessons</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format($progress, 0) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full transition-all duration-500 ease-out dark:bg-gray-200" style="width: {{ $progress }}%; background-color: #000000;"></div>
                            </div>
                            @if($isCompleted && $enrollment['status'] !== 'completed')
                                <div class="mt-6">
                                    <button
                                        type="button"
                                        wire:click="finishEnrollment({{ $enrollment['id'] }})"
                                        wire:confirm="Are you sure you want to finish this enrollment? It will be marked as completed and removed from the dashboard."
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Mark as Completed
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Lessons List -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($lessonTitles as $lessonNum => $lessonTitle)
                                @php
                                    $isAttended = in_array($lessonNum, $attendedLessons);
                                @endphp
                                <button
                                    type="button"
                                    wire:click="toggleLessonAttendance({{ $enrollment['id'] }}, {{ $lessonNum }})"
                                    wire:loading.attr="disabled"
                                    class="flex items-center gap-3 p-3 rounded-lg border transition-all hover:shadow-sm cursor-pointer w-full text-left {{ $isAttended ? 'bg-gray-50 dark:bg-gray-700/30 border-gray-300 dark:border-gray-600' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                                    <div class="flex-shrink-0">
                                        @if($isAttended)
                                            <div class="w-6 h-6 rounded flex items-center justify-center shadow-sm" style="background-color: #000000;">
                                                <svg class="w-4 h-4 text-white dark:text-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 border-2 border-gray-400 dark:border-gray-500 rounded bg-white dark:bg-gray-800"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">Lesson {{ $lessonNum }}: {{ $lessonTitle }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if(empty($enrollments))
                    <div class="text-center py-8">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No students enrolled</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a new student.</p>
                        <div class="mt-6">
                            <a href="{{ \App\Filament\Resources\SuynlEnrollmentResource::getUrl('create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                Add Student
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>

