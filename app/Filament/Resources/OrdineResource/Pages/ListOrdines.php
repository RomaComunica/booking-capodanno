<?php

namespace App\Filament\Resources\OrdineResource\Pages;

use App\Filament\Resources\OrdineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrdines extends ListRecords
{
    protected static string $resource = OrdineResource::class;
    
    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuovo Ordine')
                ->icon('heroicon-o-plus'),
        ];
    }
}
