<?php

namespace App\Filament\Resources\OrdineResource\Pages;

use App\Filament\Resources\OrdineResource;
use Filament\Resources\Pages\ListRecords;

class ListOrdines extends ListRecords
{
    protected static string $resource = OrdineResource::class;
    
    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
