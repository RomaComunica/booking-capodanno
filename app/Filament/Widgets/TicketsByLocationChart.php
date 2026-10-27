<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;

class TicketsByLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Biglietti per Location';
    
    protected static ?int $sort = 6;
    
    public ?int $year = null;
    public ?string $period = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    protected $listeners = [
        'dashboard-filter-changed' => 'updateDashboardFilter',
        'year-changed' => 'updateYear',
    ];

    public function updateDashboardFilter($filters): void
    {
        if (is_array($filters)) {
            $this->year = isset($filters['year']) ? (int) $filters['year'] : $this->year;
            $this->period = $filters['period'] ?? 'tutto_anno';
            $this->dateFrom = $filters['date_from'] ?? null;
            $this->dateTo = $filters['date_to'] ?? null;
        }
    }

    public function updateYear($year): void
    {
        $this->year = (int) $year;
    }

    protected function getData(): array
    {
        $year = $this->year ?? (int) \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $period = $this->period ?? StatisticsService::getDefaultDashboardPeriod();
        $service = app(StatisticsService::class);
        
        $locationData = $service->getTopLocations($year, 10, $period, $this->dateFrom, $this->dateTo);
        
        $labels = [];
        $data = [];
        $colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16'];
        
        foreach ($locationData as $item) {
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
