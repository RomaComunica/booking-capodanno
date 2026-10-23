<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    public array $pagamentiToSync = [];

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        if (!empty($this->pagamentiToSync) && $this->record) {
            $this->record->syncPagamentiLocation($this->pagamentiToSync);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
