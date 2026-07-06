<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;

class RevenueLast4YearsChart extends ChartWidget
{
    protected static ?string $heading = 'Totale Fatturato (Ultimi 4 Anni)';
    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $service = app(StatisticsService::class);
        $data = $service->getRevenueLastNYears(4);
        
        $revenueData = array_column($data, 'revenue');
        $labels = array_column($data, 'year');
        
        return [
            'datasets' => [
                [
                    'label' => 'Fatturato (€)',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)', // Emerald
                    'borderColor' => '#10b981',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
