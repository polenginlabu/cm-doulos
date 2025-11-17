<?php

namespace App\Filament\Resources\CellGroupResource\Pages;

use App\Filament\Resources\CellGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCellGroup extends EditRecord
{
    protected static string $resource = CellGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

