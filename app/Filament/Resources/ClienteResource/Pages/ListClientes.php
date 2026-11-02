<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    public function getTitle(): string
    {
        $count = \App\Models\Cliente::count();
        return "Clienti ({$count})";
    }

    public function getHeading(): string
    {
        $count = \App\Models\Cliente::count();
        return "Clienti ({$count})";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuovo Cliente')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
