<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class RevenuePerDayChart extends ChartWidget
{
    protected static ?string $heading = 'Fatturato per Giorno (Mese Corrente)';
    protected static ?int $sort = 5;
    
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
        
        $revenueData = array_column($dailyData, 'revenue');
        $labels = array_column($dailyData, 'day');
        
        return [
            'datasets' => [
                [
                    'label' => 'Fatturato Giornaliero (€)',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)', // Blue
                    'borderColor' => '#3b82f6',
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
