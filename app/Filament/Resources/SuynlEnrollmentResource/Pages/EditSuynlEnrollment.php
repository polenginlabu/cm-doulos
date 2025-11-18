<?php

namespace App\Filament\Resources\SuynlEnrollmentResource\Pages;

use App\Filament\Resources\SuynlEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuynlEnrollment extends EditRecord
{
    protected static string $resource = SuynlEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

