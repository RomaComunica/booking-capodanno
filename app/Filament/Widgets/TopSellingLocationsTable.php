<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\RigaOrdine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopSellingLocationsTable extends BaseWidget
{
    protected static ?int $sort = 10;
    protected int | string | array $columnSpan = 'full';
    
    public ?int $year = null;

    protected $listeners = [
        'year-changed' => 'updateYear',
        'year-changed-stats' => 'updateYear'
    ];

    public function mount(): void
    {
        $this->year = (int)\App\Models\Setting::getVal('anno_riferimento', date('Y'));
    }

    public function updateYear($year)
    {
        $this->year = (int)$year;
        $this->resetTable();
    }

    public function getTotalLocationsCount(): int
    {
        $year = $this->year ?? (int)\App\Models\Setting::getVal('anno_riferimento', date('Y'));

        return (int) RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->where('ordini.statoOrdine', 2)
            ->whereYear('ordini.dataOrdine', $year)
            ->whereNotNull('ordini.idLocationOrdine')
            ->where('ordini.idLocationOrdine', '>', 0)
            ->distinct('ordini.idLocationOrdine')
            ->count('ordini.idLocationOrdine');
    }
    
    protected function getTableHeading(): ?string
    {
        $count = $this->getTotalLocationsCount();
        return "I Più Venduti - Location ({$count})";
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->idLocation;
    }

    public function table(Table $table): Table
    {
        $year = $this->year ?? (int)\App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $totalLocations = $this->getTotalLocationsCount();

        return $table
            ->query(
                RigaOrdine::query()
                    ->join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                    ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
                    ->where('ordini.statoOrdine', 2)
                    ->whereYear('ordini.dataOrdine', $year)
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
