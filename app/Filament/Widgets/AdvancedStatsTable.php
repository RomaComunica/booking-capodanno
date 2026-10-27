<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\RigaOrdine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdvancedStatsTable extends BaseWidget
{
    protected static ?int $sort = 2;
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
    
    protected function getTableHeading(): ?string
    {
        $year = $this->year ?? (int) \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $period = $this->period ?? StatisticsService::getDefaultDashboardPeriod();
        $range = StatisticsService::resolvePeriodRange($year, $period, $this->dateFrom, $this->dateTo);

        return "Ordini Recenti ({$range['short_label']})";
    }

    public function table(Table $table): Table
    {
        $year = $this->year ?? (int) \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $period = $this->period ?? StatisticsService::getDefaultDashboardPeriod();
        $range = StatisticsService::resolvePeriodRange($year, $period, $this->dateFrom, $this->dateTo);

        return $table
            ->query(
                RigaOrdine::query()
                    ->join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                    ->leftJoin('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
                    ->leftJoin('partner', 'ordini.idPartnerOrdine', '=', 'partner.idPartner')
                    ->select(
                        'righe_ordine.*', 
                        'ordini.dataOrdine', 
                        'ordini.numeroOrdine as ordine_num',
                        'location.nomeLocation',
                        'partner.nomePartner',
                        'righe_ordine.nomeIngressoOrdine',
                        DB::raw('(righe_ordine.quantitaIngressoOrdine * COALESCE(righe_ordine.prezzoIngressoOrdine, 0)) as totaleRiga')
                    )
                    ->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
                    ->where('ordini.statoOrdine', 2)
                    ->orderBy('ordini.dataOrdine', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('ordine_num')
                    ->label('Ordine')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dataOrdine')
                    ->dateTime('d/m/Y H:i')
                    ->label('Data'),
                Tables\Columns\TextColumn::make('nomeLocation')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomeIngressoOrdine')
                    ->label('Biglietto'),
                Tables\Columns\TextColumn::make('quantitaIngressoOrdine')
                    ->label('Qtà')
                    ->numeric(),
                Tables\Columns\TextColumn::make('totaleRiga')
                    ->money('eur')
                    ->label('Totale'),
                Tables\Columns\TextColumn::make('nomePartner')
                    ->label('Partner')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Location')
                    ->options(\App\Models\Location::pluck('nomeLocation', 'nomeLocation'))
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->where('location.nomeLocation', $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('partner')
                    ->label('Partner')
                    ->options(\App\Models\Partner::pluck('nomePartner', 'idPartner'))
                    ->query(function (Builder $query, array $data) {
                         if (empty($data['value'])) return $query;
                         return $query->where('ordini.idPartnerOrdine', $data['value']);
                    }),
            ]);
    }
}
