<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    public array $pagamentiToSync = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record && $this->record->idLocation) {
            $map = $this->record->getPagamentiStatoMap();
            foreach ([1, 2, 3, 4, 5] as $idPag) {
                $data['pagamento_' . $idPag] = $map[$idPag] ?? true;
            }
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pagamentiToSync = [];
        foreach ([1, 2, 3, 4, 5] as $idPag) {
            $key = 'pagamento_' . $idPag;
            if (array_key_exists($key, $data)) {
                $this->pagamentiToSync[$idPag] = (bool) $data[$key];
                unset($data[$key]);
            }
        }
        return $data;
    }

    protected function afterSave(): void
    {
        if (!empty($this->pagamentiToSync) && $this->record) {
            $this->record->syncPagamentiLocation($this->pagamentiToSync);
        }
    }

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
