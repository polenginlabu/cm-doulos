<?php

namespace App\Filament\Resources\ConsolidationResource\Pages;

use App\Filament\Resources\ConsolidationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EditConsolidation extends EditRecord
{
    protected static string $resource = ConsolidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function resolveRecord(int | string $key): Model
    {
        // If key is 0 or empty, it's invalid
        if (empty($key) || $key === '0' || $key === 0) {
            abort(404, 'Invalid record ID.');
        }

        // Try to find the consolidation member by user_id
        // If not found, we'll create one on save
        $record = \App\Models\ConsolidationMember::where('user_id', $key)->first();

        if (!$record) {
            // Create a new consolidation member record for this user
            $user = \App\Models\User::find($key);
            if (!$user) {
                abort(404, 'User not found.');
            }

            $record = new \App\Models\ConsolidationMember();
            $record->user_id = $key;
            $record->status = 'not_contacted';
            $record->added_at = now();
            $record->save();
        }

        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update user's attendance status if provided
        $attendanceStatus = $data['attendance_status'] ?? null;
        if ($attendanceStatus && $this->record->user) {
            $this->record->user->attendance_status = $attendanceStatus;
            $this->record->user->save();

            // If changed to regular, mark consolidation as completed
            if ($attendanceStatus === 'regular') {
                $data['status'] = 'completed';
                $data['completed_at'] = Carbon::now();
            }
        }

        // If consolidation status is completed, automatically set attendance status to regular
        if (isset($data['status']) && $data['status'] === 'completed' && $this->record->user) {
            $this->record->user->attendance_status = 'regular';
            $this->record->user->save();

            if (!isset($data['completed_at'])) {
                $data['completed_at'] = Carbon::now();
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the user's current attendance_status into the form
        if ($this->record->user) {
            $data['attendance_status'] = $this->record->user->attendance_status;
        }

        return $data;
    }
}

