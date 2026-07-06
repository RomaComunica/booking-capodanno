<?php

namespace App\Filament\Exports;

use App\Models\Cliente;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ClienteExporter extends Exporter
{
    protected static ?string $model = Cliente::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('emailClienteOrdine')
                ->label('Email'),
            ExportColumn::make('nomeClienteOrdine')
                ->label('Nome'),
            ExportColumn::make('cognomeClienteOrdine')
                ->label('Cognome'),
            ExportColumn::make('telefonoClienteOrdine')
                ->label('Telefono'),
            ExportColumn::make('ordini_count')
                ->label('Numero Ordini')
                ->state(fn ($record) => $record->ordini->count()),
            ExportColumn::make('totale_speso')
                ->label('Totale Speso (€)')
                ->state(fn ($record) => $record->ordini->sum('totaleOrdine')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your cliente export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
