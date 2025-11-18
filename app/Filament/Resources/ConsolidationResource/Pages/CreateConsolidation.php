<?php

namespace App\Filament\Resources\ConsolidationResource\Pages;

use App\Filament\Resources\ConsolidationResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;

class CreateConsolidation extends CreateRecord
{
    protected static string $resource = ConsolidationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'not_contacted';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Update user's attendance status if provided in form
        $attendanceStatus = $this->form->getState()['attendance_status'] ?? null;
        if ($attendanceStatus && $this->record->user) {
            $this->record->user->attendance_status = $attendanceStatus;
            $this->record->user->save();

            // If set to regular, mark consolidation as completed
            if ($attendanceStatus === 'regular') {
                $this->record->status = 'completed';
                $this->record->completed_at = now();
                $this->record->save();
            }
        }

        // If consolidation status is completed, automatically set attendance status to regular
        if ($this->record->status === 'completed' && $this->record->user) {
            $this->record->user->attendance_status = 'regular';
            $this->record->user->save();
        }
    }
}

