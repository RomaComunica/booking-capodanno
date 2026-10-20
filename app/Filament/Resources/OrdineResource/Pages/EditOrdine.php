<?php

namespace App\Filament\Resources\OrdineResource\Pages;

use App\Filament\Resources\OrdineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdine extends EditRecord
{
    protected static string $resource = OrdineResource::class;

    protected function afterSave(): void
    {
        $record = $this->record;
        if (!$record) {
            return;
        }

        $record->load('righe');

        if ((float) $record->totaleOrdine <= 0 && $record->righe->isNotEmpty()) {
            $calculatedTotal = $record->righe->sum(function ($riga) {
                $rowTot = (float) ($riga->totaleRiga ?? 0);
                if ($rowTot > 0) {
                    return $rowTot;
                }
                return (float) ($riga->prezzoIngressoOrdine ?? 0) * max(1, (int) ($riga->quantitaIngressoOrdine ?? 1));
            });

            if ($calculatedTotal > 0) {
                $record->updateQuietly(['totaleOrdine' => round($calculatedTotal, 2)]);
            }
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
            Actions\DeleteAction::make(),
        ];
    }
}
