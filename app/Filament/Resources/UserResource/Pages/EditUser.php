<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
