<?php

namespace App\Filament\Resources\ConsolidationResource\Pages;

use App\Filament\Resources\ConsolidationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsolidations extends ListRecords
{
    protected static string $resource = ConsolidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Member'),
        ];
    }
}

