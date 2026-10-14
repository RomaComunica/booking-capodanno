<?php

namespace App\Filament\Resources\EntrataUscitaResource\Pages;

use App\Filament\Resources\EntrataUscitaResource;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ManageRecords;

class ManageEntrataUscitas extends ManageRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EntrataUscitaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EntrataUscitaResource\Widgets\EntrataUscitaOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            EntrataUscitaResource\Widgets\EntrataUscitaRiepilogoSpese::class,
        ];
    }
}
