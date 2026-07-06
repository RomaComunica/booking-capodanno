<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;

class TicketsByLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Biglietti per Location';
    
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
        $service = app(StatisticsService::class);
        
        $locationData = $service->getTopLocations($year, 10);
        
        $labels = [];
        $data = [];
        $colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16'];
        
        foreach ($locationData as $index => $item) {
            $labels[] = $item->nomeLocation;
            $data[] = $item->total_tickets;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Biglietti Venduti',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
