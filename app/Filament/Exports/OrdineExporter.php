<?php

namespace App\Filament\Exports;

use App\Models\Ordine;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class OrdineExporter extends Exporter
{
    protected static ?string $model = Ordine::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('numeroOrdine')
                ->label('ID Ordine'),
            ExportColumn::make('dataOrdine')
                ->label('Data Ordine'),
            ExportColumn::make('nomeClienteOrdine')
                ->label('Nome Cliente'),
            ExportColumn::make('cognomeClienteOrdine')
                ->label('Cognome Cliente'),
            ExportColumn::make('emailClienteOrdine')
                ->label('Email'),
            ExportColumn::make('telefonoClienteOrdine')
                ->label('Telefono'),
            ExportColumn::make('totaleOrdine')
                ->label('Totale (€)'),
            ExportColumn::make('statoOrdine')
                ->label('Stato')
                ->state(function ($record) {
                    return match ($record->statoOrdine) {
                        0 => 'In attesa',
                        1 => 'Pagato',
                        2 => 'Annullato',
                        default => 'Sconosciuto',
                    };
                }),
            ExportColumn::make('location.nomeLocation')
                ->label('Location'),
            ExportColumn::make('partner.nomePartner')
                ->label('Partner'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your ordine export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
