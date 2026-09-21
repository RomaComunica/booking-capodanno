<?php

namespace App\Filament\Resources\EntrataUscitaResource\Widgets;

use App\Filament\Resources\EntrataUscitaResource\Pages\ManageEntrataUscitas;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EntrataUscitaOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ManageEntrataUscitas::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totaleEntrate = (float)(clone $query)->where('tipoOperazione', 'E')->sum('importo');
        $totaleUscite = (float)(clone $query)->where('tipoOperazione', 'U')->sum('importo');
        $saldo = $totaleEntrate - $totaleUscite;

        return [
            Stat::make('Totale Entrate Extra', '€ ' . number_format($totaleEntrate, 2, ',', '.'))
                ->description('Incassi e rimborsi extra')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Totale Uscite Extra', '€ ' . number_format($totaleUscite, 2, ',', '.'))
                ->description('Spese e costi extra sostenuti')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Saldo Netto Extra', ($saldo >= 0 ? '+€ ' : '-€ ') . number_format(abs($saldo), 2, ',', '.'))
                ->description($saldo >= 0 ? 'Attivo extra del periodo' : 'Passivo extra del periodo')
                ->descriptionIcon('heroicon-m-scale')
                ->color($saldo >= 0 ? 'success' : 'danger'),
        ];
    }
}
