<?php

namespace App\Filament\Resources\EntrataUscitaResource\Pages;

use App\Filament\Resources\EntrataUscitaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEntrataUscitas extends ManageRecords
{
    protected static string $resource = EntrataUscitaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
