<?php

namespace App\Filament\Resources\SuynlEnrollmentResource\Pages;

use App\Filament\Resources\SuynlEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuynlEnrollments extends ListRecords
{
    protected static string $resource = SuynlEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('View Dashboard')
                ->icon('heroicon-o-chart-bar')
                ->url(fn () => SuynlEnrollmentResource::getUrl('index')),
            Actions\CreateAction::make(),
        ];
    }
}

