<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\Widget;

class StatsOverview extends Widget
{
    protected static string $view = 'filament.widgets.stats-overview';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    public ?int $year = null;
    public ?string $period = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    protected $listeners = [
        'dashboard-filter-changed' => 'updateDashboardFilter',
        'year-changed' => 'updateYear',
        'year-changed-stats' => 'updateYear',
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

    public function getViewData(): array
    {
        $year = $this->year ?? (int) \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $period = $this->period ?? StatisticsService::getDefaultDashboardPeriod();
        $stats = app(StatisticsService::class)->getDashboardStats($year, $period, $this->dateFrom, $this->dateTo);

        return [
            'stats' => $stats,
            'year' => $year,
            'period' => $period,
            'periodLabel' => $stats['period_label'] ?? "Tutto l'anno {$year}",
            'periodShortLabel' => $stats['period_short_label'] ?? (string) $year,
        ];
    }
}
