<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    public ?int $year = null;

    protected $listeners = [
        'year-changed' => 'updateYear',
        'year-changed-stats' => 'updateYear'
    ];

    public function updateYear($year)
    {
        $this->year = $year;
    }

    protected function getStats(): array
    {
        $year = $this->year ?? date('Y');
        $stats = app(StatisticsService::class)->getDashboardStats($year);

        return [
            Stat::make('Fatturato Totale', '€ ' . number_format($stats['total_revenue'], 2, ',', '.'))
                ->description('Anno corrente')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),

            Stat::make('Ordini Totali', number_format($stats['total_orders'], 0, ',', '.'))
                ->description($stats['pending_orders'] . ' in attesa')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),

            Stat::make('Biglietti Venduti', number_format($stats['total_tickets'], 0, ',', '.'))
                ->description('Anno corrente')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info'),

            Stat::make('Utile Netto', '€ ' . number_format($stats['total_profit'], 2, ',', '.'))
                ->description('Commissioni incassate')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Location Attive', $stats['active_locations'] . ' / ' . $stats['locations_count'])
                ->description('Totale location')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('primary'),

            Stat::make('Partner', number_format($stats['partners_count'], 0, ',', '.'))
                ->description('Partner attivi')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
