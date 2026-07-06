<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\LocationResource\Pages\StampaListaNomi;
use App\Filament\Resources\LocationResource\Pages\StampaEntrateUscite;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('stampa_nomi')
                ->label('Lista Nomi')
                ->icon('heroicon-o-list-bullet')
                ->url(StampaListaNomi::getUrl()),
            
            Actions\Action::make('stampa_entrate')
                ->label('Report Entrate')
                ->icon('heroicon-o-currency-euro')
                ->url(StampaEntrateUscite::getUrl()),

            Actions\CreateAction::make(),
        ];
    }
}
