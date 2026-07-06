<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;

class RevenuePerMonthChart extends ChartWidget
{
    protected static ?string $heading = 'Fatturato per Mese';
    
    protected static ?int $sort = 4;
    
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
        $service = app(StatisticsService::class);
        
        $monthlyData = $service->getMonthlyRevenue($year);
        
        $revenueData = array_column($monthlyData, 'revenue');
        
        return [
            'datasets' => [
                [
                    'label' => 'Fatturato ' . $year . ' (€)',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => '#22c55e',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
