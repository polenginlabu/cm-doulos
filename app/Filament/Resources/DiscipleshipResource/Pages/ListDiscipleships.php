<?php

namespace App\Filament\Resources\DiscipleshipResource\Pages;

use App\Filament\Resources\DiscipleshipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDiscipleships extends ListRecords
{
    protected static string $resource = DiscipleshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

