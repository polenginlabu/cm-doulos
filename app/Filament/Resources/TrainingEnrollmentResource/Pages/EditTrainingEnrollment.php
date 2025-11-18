<?php

namespace App\Filament\Resources\TrainingEnrollmentResource\Pages;

use App\Filament\Resources\TrainingEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingEnrollment extends EditRecord
{
    protected static string $resource = TrainingEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

