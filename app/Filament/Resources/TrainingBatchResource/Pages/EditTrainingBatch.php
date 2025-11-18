<?php

namespace App\Filament\Resources\TrainingBatchResource\Pages;

use App\Filament\Resources\TrainingBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingBatch extends EditRecord
{
    protected static string $resource = TrainingBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

