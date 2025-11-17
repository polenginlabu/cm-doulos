<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Discipleship;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class QuickAttendance extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string $view = 'filament.pages.quick-attendance';

    protected static ?string $navigationLabel = 'Quick Attendance';

    protected static ?string $title = 'Mark Weekly Attendance';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public $selectedDate;
    public $networkMembers = [];
    public $attendanceData = [];
    public $attendanceSummary = [];
    public $availableWeeks = [];

    public function mount(): void
    {
        // Default to current week's Sunday
        $this->selectedDate = Carbon::now()->startOfWeek()->addDays(6)->format('Y-m-d');
        $this->loadNetworkMembers();
        $this->loadAvailableWeeks();
        $this->loadExistingAttendance();
        $this->calculateSummary();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Select Week')
                    ->schema([
                        DatePicker::make('selectedDate')
                            ->label('Sunday Date')
                            ->default($this->selectedDate)
                            ->required()
                            ->weekStartsOnSunday()
                            ->displayFormat('M d, Y')
                            ->native(false)
                            ->reactive()
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('previousWeek')
                                    ->icon('heroicon-o-chevron-left')
                                    ->action(function () {
                                        $date = Carbon::parse($this->selectedDate);
                                        $this->selectedDate = $date->subWeek()->format('Y-m-d');
                                        $this->loadExistingAttendance();
                                        $this->calculateSummary();
                                    })
                            )
                            ->prefixAction(
                                \Filament\Forms\Components\Actions\Action::make('nextWeek')
                                    ->icon('heroicon-o-chevron-right')
                                    ->action(function () {
                                        $date = Carbon::parse($this->selectedDate);
                                        $this->selectedDate = $date->addWeek()->format('Y-m-d');
                                        $this->loadExistingAttendance();
                                        $this->calculateSummary();
                                    })
                            )
                            ->afterStateUpdated(function ($state) {
                                $this->selectedDate = $state;
                                $this->loadExistingAttendance();
                                $this->calculateSummary();
                            }),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function previousWeek(): void
    {
        $date = Carbon::parse($this->selectedDate);
        $this->selectedDate = $date->subWeek()->format('Y-m-d');
        $this->loadExistingAttendance();
        $this->calculateSummary();
        $this->resetTable();
    }

    public function nextWeek(): void
    {
        $date = Carbon::parse($this->selectedDate);
        $this->selectedDate = $date->addWeek()->format('Y-m-d');
        $this->loadExistingAttendance();
        $this->calculateSummary();
        $this->resetTable();
    }

    public function goToCurrentWeek(): void
    {
        $this->selectedDate = Carbon::now()->startOfWeek()->addDays(6)->format('Y-m-d');
        $this->loadExistingAttendance();
        $this->calculateSummary();
        $this->resetTable();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadExistingAttendance();
        $this->calculateSummary();
        $this->resetTable();
    }

    public function loadAvailableWeeks(): void
    {
        $query = $this->getTableQuery();
        $userIds = $query->pluck('id')->toArray();

        if (empty($userIds)) {
            $this->availableWeeks = [];
            return;
        }

        $this->availableWeeks = Attendance::whereIn('user_id', $userIds)
            ->selectRaw('DATE(attendance_date) as week_date')
            ->groupBy('week_date')
            ->orderBy('week_date', 'desc')
            ->pluck('week_date')
            ->map(function ($date) {
                $carbon = Carbon::parse($date);
                // Get the Sunday of that week
                $sunday = $carbon->copy()->startOfWeek()->addDays(6);
                return [
                    'date' => $sunday->format('Y-m-d'),
                    'display' => $sunday->format('M d, Y'),
                    'week' => $sunday->format('M d') . ' - ' . $sunday->copy()->addDays(6)->format('M d, Y'),
                ];
            })
            ->unique('date')
            ->values()
            ->toArray();
    }

    public function getTableQuery(): Builder
    {
        if (!Auth::check()) {
            return User::query()->whereRaw('1 = 0'); // Empty query
        }

        /** @var User $user */
        $user = Auth::user();
        if (!$user || !method_exists($user, 'getNetworkUserIds')) {
            return User::query()->whereRaw('1 = 0'); // Empty query
        }

        $networkIds = $user->getNetworkUserIds();
        return User::query()
            ->whereIn('id', $networkIds)
            ->with('mentor.mentor');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Member')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        $initials = collect(explode(' ', $record->name))
                            ->map(fn($n) => strtoupper(substr($n, 0, 1)))
                            ->take(2)
                            ->implode('');

                        $html = '<div class="flex items-center gap-3">';
                        $html .= '<div class="w-10 h-10 bg-primary-600 rounded-full flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">' . e($initials) . '</div>';
                        $html .= '<div>';
                        $html .= '<p class="text-sm font-medium text-gray-900 dark:text-white">' . e($record->name) . '</p>';
                        if ($record->email) {
                            $html .= '<p class="text-xs text-gray-500 dark:text-gray-400">' . e($record->email) . '</p>';
                        }
                        $html .= '</div>';
                        $html .= '</div>';
                        return $html;
                    })
                    ->html(),
                TextColumn::make('attendance_status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => '1st',
                        'info' => '2nd',
                        'success' => '3rd',
                        'primary' => '4th',
                        'gray' => 'regular',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('direct_leader')
                    ->label('Direct Leader')
                    ->getStateUsing(function ($record) {
                        // Use the mentor relationship from User model
                        $discipleship = $record->mentor;

                        if ($discipleship && $discipleship->mentor) {
                            return $discipleship->mentor->name;
                        }

                        // Fallback: query directly
                        $discipleship = Discipleship::where('disciple_id', $record->id)
                            ->where('status', 'active')
                            ->with('mentor')
                            ->first();

                        if ($discipleship && $discipleship->mentor) {
                            return $discipleship->mentor->name;
                        }

                        return null;
                    })
                    ->placeholder('No leader')
                    ->sortable(false),
                ToggleColumn::make('attendance_present')
                    ->label('Mark Attendance')
                    ->getStateUsing(function ($record) {
                        $selectedDate = $this->selectedDate ?? null;

                        if (empty($selectedDate)) {
                            return false;
                        }

                        $date = Carbon::parse($selectedDate);
                        return Attendance::where('user_id', $record->id)
                            ->whereDate('attendance_date', $date->format('Y-m-d'))
                            ->where('attendance_type', 'sunday_service')
                            ->exists();
                    })
                    ->updateStateUsing(function ($record, $state) {
                        // Prevent automatic save, handle it manually
                        $this->toggleAttendance($record->id, 'sunday_service');
                        // Return the new state so the toggle updates visually
                        return !$state;
                    })
                    ->disabled(fn () => empty($this->selectedDate)),
            ])
            ->filters([
                SelectFilter::make('attendance_status')
                    ->label('Attendance Status')
                    ->options([
                        '1st' => '1st Time',
                        '2nd' => '2nd Time',
                        '3rd' => '3rd Time',
                        '4th' => '4th Time',
                        'regular' => 'Regular',
                    ]),
                SelectFilter::make('cell_group_id')
                    ->label('Cell Group')
                    ->relationship('cellGroup', 'name')
                    ->searchable(),
                SelectFilter::make('direct_leader_id')
                    ->label('Direct Leader')
                    ->options(function () {
                        // Get all users in the network who are mentors
                        if (!Auth::check()) {
                            return [];
                        }

                        /** @var User $user */
                        $user = Auth::user();
                        if (!$user || !method_exists($user, 'getNetworkUserIds')) {
                            return [];
                        }

                        $networkIds = $user->getNetworkUserIds();

                        // Get all users who are mentors (have active discipleships)
                        $mentorIds = Discipleship::whereIn('mentor_id', $networkIds)
                            ->where('status', 'active')
                            ->distinct()
                            ->pluck('mentor_id')
                            ->toArray();

                        return User::whereIn('id', $mentorIds)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || empty($data['value'])) {
                            return $query;
                        }

                        // Filter users who have this mentor
                        $mentorId = $data['value'];
                        return $query->whereHas('discipleships', function ($q) use ($mentorId) {
                            $q->where('mentor_id', $mentorId)
                                ->where('status', 'active');
                        });
                    }),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->defaultSort('name')
            ->paginated([10]);
    }

    public function calculateSummary(): void
    {
        if (empty($this->selectedDate)) {
            $query = $this->getTableQuery();
            $this->attendanceSummary = [
                'sunday_service' => 0,
                'total_members' => $query->count(),
            ];
            return;
        }

        $date = Carbon::parse($this->selectedDate);
        $query = $this->getTableQuery();
        $userIds = $query->pluck('id')->toArray();

        $this->attendanceSummary = [
            'sunday_service' => Attendance::whereIn('user_id', $userIds)
                ->whereDate('attendance_date', $date->format('Y-m-d'))
                ->where('attendance_type', 'sunday_service')
                ->count(),
            'total_members' => count($userIds),
        ];
    }

    public function loadNetworkMembers(): void
    {
        if (!Auth::check()) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        if (!$user || !method_exists($user, 'getNetworkUserIds')) {
            return;
        }

        $networkIds = $user->getNetworkUserIds();
        $this->networkMembers = User::whereIn('id', $networkIds)
            ->orderBy('name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'attendance_status' => $member->attendance_status,
                ];
            })
            ->toArray();
    }

    public function loadExistingAttendance(): void
    {
        if (empty($this->selectedDate)) {
            $this->attendanceData = [];
            return;
        }

        $date = Carbon::parse($this->selectedDate);
        $query = $this->getTableQuery();
        $userIds = $query->pluck('id')->toArray();

        if (empty($userIds)) {
            $this->attendanceData = [];
            return;
        }

        $existingAttendances = Attendance::whereIn('user_id', $userIds)
            ->whereDate('attendance_date', $date->format('Y-m-d'))
            ->get()
            ->groupBy('user_id');

        $this->attendanceData = [];
        foreach ($userIds as $userId) {
            $userAttendances = $existingAttendances->get($userId, collect());

            $this->attendanceData[$userId] = [
                'crossover' => $userAttendances->where('attendance_type', 'crossover')->isNotEmpty(),
                'wildsons' => $userAttendances->where('attendance_type', 'wildsons')->isNotEmpty(),
                'sunday_service' => $userAttendances->where('attendance_type', 'sunday_service')->isNotEmpty(),
            ];
        }
    }

    public function toggleAttendance($userId, $serviceType): void
    {
        if (empty($this->selectedDate)) {
            Notification::make()
                ->title('Please select a date first')
                ->warning()
                ->send();
            return;
        }

        $date = Carbon::parse($this->selectedDate);

        // Check if attendance exists in database
        $existingAttendance = Attendance::where('user_id', $userId)
            ->whereDate('attendance_date', $date->format('Y-m-d'))
            ->where('attendance_type', $serviceType)
            ->first();

        if ($existingAttendance) {
            // Remove attendance
            $existingAttendance->delete();
        } else {
            // Create attendance
            Attendance::create([
                'user_id' => $userId,
                'attendance_date' => $date->format('Y-m-d'),
                'attendance_type' => $serviceType,
                'is_present' => true,
            ]);
        }

        // Update user stats (only for sunday_service)
        if ($serviceType === 'sunday_service') {
            $this->updateUserStats($userId);
        }

        $this->calculateSummary();
        $this->loadExistingAttendance();

        // Reset table to refresh the data
        $this->resetTable();

        // Don't show notification for every toggle to avoid spam
    }

    public function markAllForService($serviceType): void
    {
        if (empty($this->selectedDate)) {
            Notification::make()
                ->title('Please select a date first')
                ->warning()
                ->send();
            return;
        }

        $date = Carbon::parse($this->selectedDate);
        $query = $this->getTableQuery();
        $userIds = $query->pluck('id')->toArray();

        foreach ($userIds as $userId) {
            $exists = Attendance::where('user_id', $userId)
                ->whereDate('attendance_date', $date->format('Y-m-d'))
                ->where('attendance_type', $serviceType)
                ->exists();

            if (!$exists) {
                Attendance::create([
                    'user_id' => $userId,
                    'attendance_date' => $date->format('Y-m-d'),
                    'attendance_type' => $serviceType,
                    'is_present' => true,
                ]);

                if ($serviceType === 'sunday_service') {
                    $this->updateUserStats($userId);
                }
            }
        }

        $this->loadExistingAttendance();
        $this->calculateSummary();
        $this->resetTable();

        Notification::make()
            ->title('All members marked for ' . ucfirst(str_replace('_', ' ', $serviceType)))
            ->success()
            ->send();
    }

    public function unmarkAllForService($serviceType): void
    {
        if (empty($this->selectedDate)) {
            return;
        }

        $date = Carbon::parse($this->selectedDate);
        $query = $this->getTableQuery();
        $userIds = $query->pluck('id')->toArray();

        Attendance::whereIn('user_id', $userIds)
            ->whereDate('attendance_date', $date->format('Y-m-d'))
            ->where('attendance_type', $serviceType)
            ->delete();

        foreach ($userIds as $userId) {
            if ($serviceType === 'sunday_service') {
                $this->updateUserStats($userId);
            }
        }

        $this->loadExistingAttendance();
        $this->calculateSummary();
        $this->resetTable();

        Notification::make()
            ->title('All members unmarked for ' . ucfirst(str_replace('_', ' ', $serviceType)))
            ->success()
            ->send();
    }

    protected function updateUserStats($userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        // Count only Sunday Service attendances for status tracking
        $sundayAttendances = $user->attendances()
            ->where('attendance_type', 'sunday_service')
            ->count();

        $user->total_attendances = $sundayAttendances;

        // Update attendance status based on Sunday Service only
        if ($sundayAttendances >= 4) {
            $user->attendance_status = 'regular';
        } elseif ($sundayAttendances == 3) {
            $user->attendance_status = '4th';
        } elseif ($sundayAttendances == 2) {
            $user->attendance_status = '3rd';
        } elseif ($sundayAttendances == 1) {
            $user->attendance_status = '2nd';
        } else {
            $user->attendance_status = '1st';
        }

        // Update dates
        $firstAttendance = $user->attendances()
            ->where('attendance_type', 'sunday_service')
            ->orderBy('attendance_date')
            ->first();

        if ($firstAttendance) {
            $user->first_attendance_date = $firstAttendance->attendance_date;
        }

        $lastAttendance = $user->attendances()
            ->where('attendance_type', 'sunday_service')
            ->orderBy('attendance_date', 'desc')
            ->first();

        if ($lastAttendance) {
            $user->last_attendance_date = $lastAttendance->attendance_date;
        }

        $user->save();
    }
}


