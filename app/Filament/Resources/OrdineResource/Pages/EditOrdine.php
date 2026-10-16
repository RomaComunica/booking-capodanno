<?php

namespace App\Filament\Resources\OrdineResource\Pages;

use App\Filament\Resources\OrdineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdine extends EditRecord
{
    protected static string $resource = OrdineResource::class;

    protected function getRedirectUrl(): string
    {
        if (!empty($this->previousUrl) && !str_contains($this->previousUrl, '/edit') && !str_contains($this->previousUrl, '/create') && !str_contains($this->previousUrl, 'livewire')) {
            return $this->previousUrl;
        }

        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
