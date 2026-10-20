<?php

namespace App\Filament\Resources\OrdineResource\Pages;

use App\Filament\Resources\OrdineResource;
use App\Models\Ordine;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdine extends CreateRecord
{
    protected static string $resource = OrdineResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        if (!$record) {
            return;
        }

        $record->load(['righe', 'location', 'partner']);

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
                $record->totaleOrdine = round($calculatedTotal, 2);
            }
        }

        // Send confirmation/voucher email when manual order is created in Completato (2) or In corso (0) status
        if ((int) $record->statoOrdine !== 1) {
            Ordine::sendAutoEmail($record);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
