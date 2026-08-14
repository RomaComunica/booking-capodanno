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

    protected $listeners = [
        'year-changed' => 'updateYear',
        'year-changed-stats' => 'updateYear'
    ];

    public function updateYear($year)
    {
        $this->year = $year;
    }

    public function getViewData(): array
    {
        $year = $this->year ?? \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $stats = app(StatisticsService::class)->getDashboardStats($year);

        return [
            'stats' => $stats,
            'year' => $year,
        ];
    }
}
