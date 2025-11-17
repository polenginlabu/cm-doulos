<?php

namespace App\Filament\Resources\CellGroupResource\Pages;

use App\Filament\Resources\CellGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCellGroups extends ListRecords
{
    protected static string $resource = CellGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

