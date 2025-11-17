<?php

namespace App\Filament\Resources\DiscipleshipResource\Pages;

use App\Filament\Resources\DiscipleshipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDiscipleship extends EditRecord
{
    protected static string $resource = DiscipleshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

