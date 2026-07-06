<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\RigaOrdine;
use Illuminate\Database\Eloquent\Builder;

class AdvancedStatsTable extends BaseWidget
{
    protected static ?int $sort = 2;
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
        return 'Ordini Recenti';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RigaOrdine::query()
                    ->join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                    ->join('tipologia_ingresso', 'righe_ordine.idIngressoOrdine', '=', 'tipologia_ingresso.idIngressoCont')
                    ->join('location', 'tipologia_ingresso.idLocation', '=', 'location.idLocation')
                    ->leftJoin('partner', 'ordini.idPartnerOrdine', '=', 'partner.idPartner')
                    ->select(
                        'righe_ordine.*', 
                        'ordini.dataOrdine', 
                        'ordini.numeroOrdine as ordine_num',
                        'location.nomeLocation',
                        'partner.nomePartner',
                        'righe_ordine.nomeIngressoOrdine'
                    )
                    ->when($this->year, function ($query) {
                        return $query->whereYear('ordini.dataOrdine', $this->year);
                    })
                    ->orderBy('ordini.dataOrdine', 'desc')
                    ->limit(50)
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
