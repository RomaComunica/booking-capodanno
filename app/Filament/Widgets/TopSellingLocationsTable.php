<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\RigaOrdine;
use Illuminate\Support\Facades\DB;

class TopSellingLocationsTable extends BaseWidget
{
    protected static ?int $sort = 10;
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

    public function mount(): void
    {
        $this->year = (int) \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $this->period = StatisticsService::getDefaultDashboardPeriod();
    }

    public function updateDashboardFilter($filters): void
    {
        if (is_array($filters)) {
            $this->year = isset($filters['year']) ? (int) $filters['year'] : $this->year;
            $this->period = $filters['period'] ?? 'tutto_anno';
            $this->dateFrom = $filters['date_from'] ?? null;
            $this->dateTo = $filters['date_to'] ?? null;
            $this->resetTable();
        }
    }

    public function updateYear($year): void
    {
        $this->year = (int) $year;
        $this->resetTable();
    }

    public function getTotalLocationsCount(): int
    {
        $year = $this->year ?? (int) \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $period = $this->period ?? StatisticsService::getDefaultDashboardPeriod();
        $range = StatisticsService::resolvePeriodRange($year, $period, $this->dateFrom, $this->dateTo);

        return (int) RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->where('ordini.statoOrdine', 2)
            ->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
            ->whereNotNull('ordini.idLocationOrdine')
            ->where('ordini.idLocationOrdine', '>', 0)
            ->distinct('ordini.idLocationOrdine')
            ->count('ordini.idLocationOrdine');
    }
    
    protected function getTableHeading(): ?string
    {
        $year = $this->year ?? (int) \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $period = $this->period ?? StatisticsService::getDefaultDashboardPeriod();
        $range = StatisticsService::resolvePeriodRange($year, $period, $this->dateFrom, $this->dateTo);
        $count = $this->getTotalLocationsCount();

        return "I Più Venduti - Location ({$count}) — {$range['short_label']}";
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->idLocation;
    }

    public function table(Table $table): Table
    {
        $year = $this->year ?? (int) \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $period = $this->period ?? StatisticsService::getDefaultDashboardPeriod();
        $range = StatisticsService::resolvePeriodRange($year, $period, $this->dateFrom, $this->dateTo);
        $totalLocations = $this->getTotalLocationsCount();

        return $table
            ->query(
                RigaOrdine::query()
                    ->join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                    ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
                    ->where('ordini.statoOrdine', 2)
                    ->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
                    ->select([
                        'location.idLocation',
                        'location.nomeLocation',
                        DB::raw('SUM(righe_ordine.quantitaIngressoOrdine) as total_tickets'),
                        DB::raw('SUM(righe_ordine.prezzoIngressoOrdine * righe_ordine.quantitaIngressoOrdine) as total_revenue'),
                    ])
                    ->groupBy('location.idLocation', 'location.nomeLocation')
                    ->orderByDesc('total_tickets')
            )
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('nomeLocation')
                    ->label("Location ({$totalLocations})")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_tickets')
                    ->label('Biglietti Venduti')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Fatturato Generato')
                    ->money('eur')
                    ->sortable(),
            ]);
    }
}
