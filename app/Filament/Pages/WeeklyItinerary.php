<?php

namespace App\Filament\Pages;

use App\Models\ItineraryActivity;
use App\Models\ItineraryItem;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class WeeklyItinerary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Weekly Itinerary';

    protected static ?string $title = 'Weekly Itinerary';

    protected static ?string $navigationGroup = 'Itinerary';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.weekly-itinerary';

    public string $weekStart = '';
    public int $selectedDay = 0;
    public array $weekDays = [];
    public array $activityGroups = [];
    public array $viewableUsers = [];
    public ?int $viewUserId = null;

    public string $newActivityName = '';
    public bool $showActivityForm = false;
    public string $freeTextActivity = '';

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function mount(): void
    {
        $this->weekStart = $this->getCurrentWeekStart()->format('Y-m-d');
        $this->selectedDay = (int) Carbon::now()->dayOfWeekIso - 1;
        $this->loadViewableUsers();
        $this->viewUserId = $this->resolveDefaultViewUserId();
        $this->refreshPlanner();
    }

    public function updatedViewUserId(): void
    {
        $this->viewUserId = $this->resolveDefaultViewUserId();
        $this->refreshPlanner();
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->format('Y-m-d');
        $this->refreshPlanner();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->format('Y-m-d');
        $this->refreshPlanner();
    }


    public function goToCurrentWeek(): void
    {
        $this->weekStart = $this->getCurrentWeekStart()->format('Y-m-d');
        $this->selectedDay = (int) Carbon::now()->dayOfWeekIso - 1;
        $this->refreshPlanner();
    }

    public function selectDay(int $dayIndex): void
    {
        if ($dayIndex < 0 || $dayIndex > 6) {
            return;
        }

        $this->selectedDay = $dayIndex;
    }

    public function addActivityToSelectedDay(int $activityId): void
    {
        $this->addActivityToDay($activityId, $this->selectedDay);
    }

    public function addActivityToDay(int $activityId, int $dayIndex): void
    {
        if (!$this->canEdit()) {
            return;
        }

        if ($dayIndex < 0 || $dayIndex > 6) {
            return;
        }

        $activity = ItineraryActivity::where('id', $activityId)
            ->where('is_active', true)
            ->first();

        if (!$activity) {
            return;
        }

        if ($activity->user_id && $activity->user_id !== $this->viewUserId) {
            return;
        }

        $position = ItineraryItem::query()
            ->where('user_id', $this->viewUserId)
            ->whereDate('week_start_date', $this->weekStart)
            ->where('day_of_week', $dayIndex)
            ->max('position');

        ItineraryItem::create([
            'user_id' => $this->viewUserId,
            'activity_id' => $activityId,
            'week_start_date' => $this->weekStart,
            'day_of_week' => $dayIndex,
            'position' => $position !== null ? $position + 1 : 0,
        ]);

        $this->refreshPlanner();
    }

    public function syncDayItems(int $dayIndex, array $itemIds): void
    {
        if (!$this->canEdit()) {
            return;
        }

        if ($dayIndex < 0 || $dayIndex > 6) {
            return;
        }

        $filteredIds = array_values(array_filter($itemIds, fn ($id) => is_numeric($id)));

        $items = empty($filteredIds)
            ? collect()
            : ItineraryItem::query()
                ->whereIn('id', $filteredIds)
                ->where('user_id', $this->viewUserId)
                ->whereDate('week_start_date', $this->weekStart)
                ->get()
                ->keyBy('id');

        foreach ($filteredIds as $position => $itemId) {
            if (!isset($items[$itemId])) {
                continue;
            }

            $items[$itemId]->update([
                'day_of_week' => $dayIndex,
                'position' => $position,
            ]);
        }

        $this->loadWeekDays();
    }

    public function removeItem(int $itemId): void
    {
        if (!$this->canEdit()) {
            return;
        }

        ItineraryItem::query()
            ->where('id', $itemId)
            ->where('user_id', $this->viewUserId)
            ->whereDate('week_start_date', $this->weekStart)
            ->delete();

        $this->refreshPlanner();
    }

    public function toggleActivityForm(): void
    {
        if (!$this->canEdit()) {
            return;
        }

        $this->showActivityForm = !$this->showActivityForm;
    }

    public function createActivity(): void
    {
        if (!$this->canEdit()) {
            return;
        }

        $this->validate([
            'newActivityName' => ['required', 'string', 'max:80'],
        ]);

        ItineraryActivity::create([
            'name' => trim($this->newActivityName),
            'user_id' => Auth::id(),
            'is_active' => true,
        ]);

        $this->newActivityName = '';
        $this->showActivityForm = false;

        $this->refreshPlanner();
    }

    public function addFreeTextToSelectedDay(): void
    {
        if (!$this->canEdit()) {
            return;
        }

        $this->validate([
            'freeTextActivity' => ['required', 'string', 'max:80'],
        ]);

        $position = ItineraryItem::query()
            ->where('user_id', $this->viewUserId)
            ->whereDate('week_start_date', $this->weekStart)
            ->where('day_of_week', $this->selectedDay)
            ->max('position');

        ItineraryItem::create([
            'user_id' => $this->viewUserId,
            'activity_id' => null,
            'custom_label' => trim($this->freeTextActivity),
            'week_start_date' => $this->weekStart,
            'day_of_week' => $this->selectedDay,
            'position' => $position !== null ? $position + 1 : 0,
        ]);

        $this->freeTextActivity = '';
        $this->refreshPlanner();
    }

    public function canEdit(): bool
    {
        return Auth::check() && $this->viewUserId === Auth::id();
    }

    protected function refreshPlanner(): void
    {
        $this->loadActivityGroups();
        $this->loadWeekDays();
    }

    protected function loadViewableUsers(): void
    {
        $this->viewableUsers = $this->resolveViewableUsers();
    }

    protected function resolveViewableUsers(): array
    {
        if (!Auth::check()) {
            return [];
        }

        /** @var User $authUser */
        $authUser = Auth::user();

        if ($authUser->is_super_admin || $authUser->is_network_admin) {
            $query = User::query();
        } elseif (method_exists($authUser, 'getNetworkUserIds')) {
            $query = User::whereIn('id', $authUser->getNetworkUserIds());
        } else {
            $query = User::where('id', $authUser->id);
        }

        if (!$authUser->is_super_admin && $authUser->gender) {
            $query->where('gender', $authUser->gender);
        }

        return $query
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (User $user) => [$user->id => $user->name])
            ->toArray();
    }

    protected function resolveDefaultViewUserId(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        $defaultId = $this->viewUserId ?? Auth::id();

        if (!empty($this->viewableUsers) && !array_key_exists($defaultId, $this->viewableUsers)) {
            $firstId = array_key_first($this->viewableUsers);
            return $firstId ? (int) $firstId : Auth::id();
        }

        return $defaultId;
    }

    protected function loadActivityGroups(): void
    {
        if (!$this->viewUserId) {
            $this->activityGroups = [];
            return;
        }

        $activities = ItineraryActivity::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $this->viewUserId);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $this->activityGroups = [
            [
                'id' => 0,
                'name' => 'General Activities',
                'icon' => 'heroicon-o-squares-plus',
                'activities' => $activities,
            ],
        ];
    }

    protected function loadWeekDays(): void
    {
        if (!$this->viewUserId) {
            $this->weekDays = [];
            return;
        }

        $items = ItineraryItem::query()
            ->with(['activity.category'])
            ->where('user_id', $this->viewUserId)
            ->whereDate('week_start_date', $this->weekStart)
            ->orderBy('day_of_week')
            ->orderBy('position')
            ->get()
            ->groupBy('day_of_week');

        $start = Carbon::parse($this->weekStart)->startOfDay();
        $days = [];

        for ($index = 0; $index < 7; $index++) {
            $date = $start->copy()->addDays($index);
            $days[] = [
                'index' => $index,
                'label' => $date->format('l'),
                'date' => $date->format('M j'),
                'items' => $items->get($index, collect())->values(),
            ];
        }

        $this->weekDays = $days;
    }

    protected function getCurrentWeekStart(): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY);
    }
}
