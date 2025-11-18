<?php

namespace App\Filament\Resources\TrainingAttendanceResource\Pages;

use App\Filament\Resources\TrainingAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingAttendance extends EditRecord
{
    protected static string $resource = TrainingAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

