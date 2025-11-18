<?php

namespace App\Filament\Resources\ConsolidationResource\Pages;

use App\Filament\Resources\ConsolidationResource;
use App\Models\ConsolidationMember;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsolidationDashboard extends Page
{
    protected static string $resource = ConsolidationResource::class;

    protected static string $view = 'filament.resources.consolidation-resource.pages.consolidation-dashboard';

    protected static ?string $title = 'Consolidation Dashboard';

    protected static ?string $navigationLabel = 'Consolidation Dashboard';

    public $members = [];
    public $search = '';
    public $statusFilter = '';
    public $attendanceStatusFilter = '';

    public function mount(): void
    {
        $this->loadMembers();
    }

    public function loadMembers(): void
    {
        // Use the same query logic as ConsolidationResource
        $query = \App\Models\User::query()
            ->whereIn('users.attendance_status', ['1st', '2nd', '3rd', '4th'])
            ->leftJoin('consolidation_members', 'users.id', '=', 'consolidation_members.user_id')
            ->leftJoin('users as consolidators', 'consolidation_members.consolidator_id', '=', 'consolidators.id')
            ->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.attendance_status',
                DB::raw("COALESCE(consolidation_members.status, 'not_contacted') as consolidation_status"),
                'consolidation_members.consolidator_id',
                DB::raw("CONCAT(consolidators.first_name, ' ', consolidators.last_name) as consolidator_name"),
                DB::raw("COALESCE(consolidation_members.added_at, users.first_attendance_date, users.created_at) as date"),
                'consolidation_members.interest',
                'consolidation_members.next_action',
                'consolidation_members.id as consolidation_id',
            ])
            ->distinct();

        // Apply network filtering if user is not super admin or network admin
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user->is_super_admin && !$user->is_network_admin) {
                $networkIds = $user->getNetworkUserIds();
                $query->whereIn('users.id', $networkIds);
            }
        }

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('users.first_name', 'like', "%{$this->search}%")
                  ->orWhere('users.last_name', 'like', "%{$this->search}%")
                  ->orWhere('users.email', 'like', "%{$this->search}%")
                  ->orWhere(DB::raw("CONCAT(consolidators.first_name, ' ', consolidators.last_name)"), 'like', "%{$this->search}%");
            });
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where(function ($q) {
                if ($this->statusFilter === 'not_contacted') {
                    $q->whereNull('consolidation_members.status')
                      ->orWhere('consolidation_members.status', 'not_contacted');
                } else {
                    $q->where('consolidation_members.status', $this->statusFilter);
                }
            });
        }

        // Apply attendance status filter
        if ($this->attendanceStatusFilter) {
            $query->where('users.attendance_status', $this->attendanceStatusFilter);
        }

        $this->members = $query->orderBy('date', 'desc')->get()->map(function ($record) {
            return [
                'id' => $record->id,
                'name' => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')),
                'email' => $record->email,
                'attendance_status' => $record->attendance_status,
                'consolidation_status' => $record->consolidation_status,
                'consolidator_name' => $record->consolidator_name ?? 'Unassigned',
                'date' => $record->date,
                'interest' => $record->interest,
                'next_action' => $record->next_action,
                'consolidation_id' => $record->consolidation_id,
            ];
        })->toArray();
    }

    public function updatedSearch(): void
    {
        $this->loadMembers();
    }

    public function updatedStatusFilter(): void
    {
        $this->loadMembers();
    }

    public function updatedAttendanceStatusFilter(): void
    {
        $this->loadMembers();
    }

    public function getTotalMembers(): int
    {
        $query = \App\Models\User::query()
            ->whereIn('attendance_status', ['1st', '2nd', '3rd', '4th']);

        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->is_super_admin && !$user->is_network_admin) {
                $networkIds = $user->getNetworkUserIds();
                $query->whereIn('id', $networkIds);
            }
        }

        return $query->count();
    }

    public function getNotContactedCount(): int
    {
        $query = \App\Models\User::query()
            ->whereIn('attendance_status', ['1st', '2nd', '3rd', '4th'])
            ->leftJoin('consolidation_members', 'users.id', '=', 'consolidation_members.user_id')
            ->where(function ($q) {
                $q->whereNull('consolidation_members.status')
                  ->orWhere('consolidation_members.status', 'not_contacted');
            });

        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->is_super_admin && !$user->is_network_admin) {
                $networkIds = $user->getNetworkUserIds();
                $query->whereIn('users.id', $networkIds);
            }
        }

        return $query->distinct('users.id')->count();
    }

    public function getInProgressCount(): int
    {
        $query = \App\Models\User::query()
            ->whereIn('attendance_status', ['1st', '2nd', '3rd', '4th'])
            ->join('consolidation_members', 'users.id', '=', 'consolidation_members.user_id')
            ->where('consolidation_members.status', 'in_progress');

        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->is_super_admin && !$user->is_network_admin) {
                $networkIds = $user->getNetworkUserIds();
                $query->whereIn('users.id', $networkIds);
            }
        }

        return $query->distinct('users.id')->count();
    }

    public function getCompletedCount(): int
    {
        $query = \App\Models\User::query()
            ->whereIn('attendance_status', ['1st', '2nd', '3rd', '4th'])
            ->join('consolidation_members', 'users.id', '=', 'consolidation_members.user_id')
            ->where('consolidation_members.status', 'completed');

        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->is_super_admin && !$user->is_network_admin) {
                $networkIds = $user->getNetworkUserIds();
                $query->whereIn('users.id', $networkIds);
            }
        }

        return $query->distinct('users.id')->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addMember')
                ->label('Add New Member')
                ->icon('heroicon-o-plus')
                ->url(fn () => ConsolidationResource::getUrl('create'))
                ->color('gray'),
        ];
    }
}

