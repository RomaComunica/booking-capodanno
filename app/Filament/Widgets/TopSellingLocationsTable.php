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

    public function updateYear($year)
    {
        $this->year = $year;
        $this->resetTable();
    }
    
    protected function getTableHeading(): ?string
    {
        return 'I Più Venduti (Location)';
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->idLocation;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RigaOrdine::query()
                    ->join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                    ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
                    ->where('ordini.statoOrdine', 2)
                    ->when($this->year, function ($query) {
                        return $query->whereYear('ordini.dataOrdine', $this->year);
                    })
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
                    ->label('Location')
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
