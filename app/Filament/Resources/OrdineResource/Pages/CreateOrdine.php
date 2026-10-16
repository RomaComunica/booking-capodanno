<?php

namespace App\Filament\Resources\OrdineResource\Pages;

use App\Filament\Resources\OrdineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdine extends CreateRecord
{
    protected static string $resource = OrdineResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
