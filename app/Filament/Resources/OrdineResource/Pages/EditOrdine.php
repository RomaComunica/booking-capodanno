<?php

namespace App\Filament\Resources\OrdineResource\Pages;

use App\Filament\Resources\OrdineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdine extends EditRecord
{
    protected static string $resource = OrdineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
