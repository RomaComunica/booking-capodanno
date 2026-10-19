<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getRedirectUrl(): string
    {
        if (!empty($this->previousUrl) && !str_contains($this->previousUrl, '/edit') && !str_contains($this->previousUrl, '/create') && !str_contains($this->previousUrl, 'livewire')) {
            return $this->previousUrl;
        }

        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['logo_dummy'] = $this->record->logo_dummy;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
