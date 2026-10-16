<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    public function getTitle(): string
    {
        $count = \App\Models\Partner::count();
        return "Partner ({$count})";
    }

    public function getHeading(): string
    {
        $count = \App\Models\Partner::count();
        return "Partner ({$count})";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
