<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use App\Models\Location;
use App\Models\Ordine;
use App\Models\TipoPagamento;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class StampaEntrateUscite extends Page
{
    protected static string $resource = LocationResource::class;

    protected static string $view = 'filament.resources.location-resource.pages.stampa-entrate-uscite';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'data_dal' => now()->startOfMonth()->format('Y-m-d'),
            'data_al' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('location_id')
                    ->label('Location (Opzionale)')
                    ->options(Location::where('attivaLocation', true)->pluck('nomeLocation', 'idLocation'))
                    ->placeholder('Tutte le location')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->updatedFilters()),
                
                Select::make('periodo_speciale')
                    ->label('Periodo Festività')
                    ->placeholder('Preimpostato...')
                    ->options([
                        'questo_mese' => 'Questo mese',
                        'halloween' => 'Halloween (1 Ott - 1 Nov)',
                        'san_valentino' => 'San Valentino (14 Gen - 14 Feb)',
                        'festa_donna' => 'Festa della Donna (8 Feb - 8 Mar)',
                    ])
                    ->reactive()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                        if (!$state) return;
                        $year = date('Y');
                        if ($state === 'questo_mese') {
                            $set('data_dal', now()->startOfMonth()->format('Y-m-d'));
                            $set('data_al', now()->endOfMonth()->format('Y-m-d'));
                        } elseif ($state === 'halloween') {
                            $set('data_dal', "{$year}-10-01");
                            $set('data_al', "{$year}-11-01");
                        } elseif ($state === 'san_valentino') {
                            $set('data_dal', "{$year}-01-14");
                            $set('data_al', "{$year}-02-14");
                        } elseif ($state === 'festa_donna') {
                            $set('data_dal', "{$year}-02-08");
                            $set('data_al', "{$year}-03-08");
                        }
                        $this->updatedFilters();
                    }),
                
                DatePicker::make('data_dal')
                    ->label('Dal')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->updatedFilters())
                    ->default(now()->startOfMonth()),
                
                DatePicker::make('data_al')
                    ->label('Al')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->updatedFilters())
                    ->default(now()->endOfMonth()),
            ])
            ->columns(4)
            ->statePath('data');
    }

    public function updatedFilters()
    {
        // Trigger re-render
    }

    public function getReportProperty()
    {
        $locationId = $this->data['location_id'] ?? null;
        $dataDal = $this->data['data_dal'] ?? null;
        $dataAl = $this->data['data_al'] ?? null;

        if (!$dataDal || !$dataAl) {
            return [];
        }

        // Query Ordini Pagati con le relative righe
        $query = \App\Models\RigaOrdine::query()
            ->join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->leftJoin('tipo_pagamenti', 'ordini.tipoPagamentoOrdine', '=', 'tipo_pagamenti.idTipoPagamento')
            ->where('ordini.statoOrdine', 2) // Completato
            ->where(function ($q) use ($dataDal, $dataAl) {
                // Filtra per Data Pagamento se presente, altrimenti Data Ordine
                $q->whereBetween('ordini.dataPagamentoOrdine', [$dataDal . ' 00:00:00', $dataAl . ' 23:59:59'])
                  ->orWhere(function ($sub) use ($dataDal, $dataAl) {
                      $sub->where('ordini.dataPagamentoOrdine', '=', '0000-00-00 00:00:00')
                          ->whereBetween('ordini.dataOrdine', [$dataDal . ' 00:00:00', $dataAl . ' 23:59:59']);
                  });
            });

        if ($locationId) {
            $query->where('ordini.idLocationOrdine', $locationId);
        }

        $righe = $query->select([
            'tipo_pagamenti.nomePagamento',
            'location.nomeLocation',
            'righe_ordine.nomeIngressoOrdine',
            'righe_ordine.quantitaIngressoOrdine',
            \Illuminate\Support\Facades\DB::raw('COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0) as prezzoIngressoOrdine'),
            'ordini.numeroOrdine'
        ])->get();

        $report = [];
        $totaleGenerale = 0;

        // Group by Payment Method -> Location -> Ticket
        $grouped = $righe->groupBy(['nomePagamento', 'nomeLocation', 'nomeIngressoOrdine']);

        foreach ($grouped as $metodo => $locations) {
            $metodoLabel = $metodo ?: 'Sconosciuto';
            foreach ($locations as $location => $tickets) {
                foreach ($tickets as $ticketName => $items) {
                    $qta = $items->sum('quantitaIngressoOrdine');
                    $tot = $items->sum(function($item) {
                        return $item->quantitaIngressoOrdine * $item->prezzoIngressoOrdine;
                    });
                    
                    $report[] = [
                        'metodo' => $metodoLabel,
                        'location' => $location,
                        'ticket' => $ticketName,
                        'ordini' => $items->pluck('numeroOrdine')->unique()->count(),
                        'quantita' => $qta,
                        'totale' => $tot
                    ];
                    $totaleGenerale += $tot;
                }
            }
        }

        return [
            'dettagli' => collect($report)->sortBy(['metodo', 'location', 'ticket'])->values()->all(),
            'totale' => $totaleGenerale,
            'periodo' => [
                'dal' => \Carbon\Carbon::parse($dataDal)->format('d/m/Y'),
                'al' => \Carbon\Carbon::parse($dataAl)->format('d/m/Y'),
            ]
        ];
    }
}
