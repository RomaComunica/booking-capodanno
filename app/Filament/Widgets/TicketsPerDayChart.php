<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;

class TicketsPerDayChart extends ChartWidget
{
    protected static ?string $heading = 'Biglietti per Giorno (Mese Corrente)';
    protected static ?int $sort = 6;
    
    public ?int $year = null;

    protected $listeners = [
        'year-changed' => 'updateYear',
    ];

    public function updateYear($year)
    {
        $this->year = $year;
    }

    protected function getData(): array
    {
        $year = $this->year ?? date('Y');
        $month = date('n'); // Current month
        
        $service = app(StatisticsService::class);
        $dailyData = $service->getDailyRevenue($year, $month);
        
        $ticketsData = array_column($dailyData, 'tickets');
        $labels = array_column($dailyData, 'day');
        
        return [
            'datasets' => [
                [
                    'label' => 'Biglietti Venduti',
                    'data' => $ticketsData,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)', // Amber
                    'borderColor' => '#f59e0b',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
