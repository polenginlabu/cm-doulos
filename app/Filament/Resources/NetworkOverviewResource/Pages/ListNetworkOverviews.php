<?php

namespace App\Filament\Resources\NetworkOverviewResource\Pages;

use App\Filament\Resources\NetworkOverviewResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListNetworkOverviews extends ListRecords
{
    protected static string $resource = NetworkOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewTree')
                ->label('View Network Tree')
                ->icon('heroicon-o-squares-2x2')
                ->url(NetworkOverviewResource::getUrl('network-tree'))
                ->color('primary'),
        ];
    }
}

