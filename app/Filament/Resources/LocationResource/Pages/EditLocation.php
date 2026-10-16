<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

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
            Actions\Action::make('save_top')
                ->label('Salva')
                ->action(fn () => $this->save())
                ->color('primary'),
            Actions\DeleteAction::make(),
        ];
    }

    public function getFooter(): \Illuminate\Contracts\View\View
    {
        return view('filament.pages.edit-location-footer');
    }
}
