<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;

class OrdersPerMonthChart extends ChartWidget
{
    protected static ?string $heading = 'Ordini per Mese';
    
    protected static ?int $sort = 3;
    
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
        
        // Count orders per month
        $ordersData = [];
        for ($month = 1; $month <= 12; $month++) {
            $count = \App\Models\Ordine::whereYear('dataOrdine', $year)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2)
                ->count();
            $ordersData[] = $count;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Ordini ' . $year,
                    'data' => $ordersData,
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                ],
            ],
            'labels' => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
