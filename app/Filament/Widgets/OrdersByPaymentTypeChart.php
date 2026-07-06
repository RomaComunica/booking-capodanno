<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;

class OrdersByPaymentTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Ordini per Tipo Pagamento';
    
    // DISABLED for SQLite compatibility (requires tipo_pagamento table)
    protected static ?int $sort = -1; // Hidden
    protected static bool $isDiscovered = false;
    
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
        
        $paymentData = $service->getRevenueByPaymentMethod($year);
        
        $labels = [];
        $data = [];
        $colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'];
        
        foreach ($paymentData as $index => $item) {
            // Handle both column names for compatibility
            $paymentName = $item->nomePagamento ?? ($item->descrizioneTipoPagamento ?? 'Sconosciuto');
            $labels[] = $paymentName;
            $data[] = $item->orders_count;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Numero Ordini',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
