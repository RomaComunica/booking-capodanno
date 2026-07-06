<?php

namespace App\Filament\Pages;

use App\Models\Partner;
use App\Models\Location;
use App\Models\RigaOrdine;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class CalcolatorePartner extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationLabel = 'Calcolatore Partner';
    
    protected static ?string $navigationGroup = 'Gestione';

    protected static string $view = 'filament.pages.calcolatore-partner';

    public ?array $data = [];
    
    public ?array $risultati = null;

    public function mount(): void
    {
        $this->form->fill([
            'data_da' => now()->startOfYear(),
            'data_a' => now(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('idPartner')
                    ->label('Partner')
                    ->options(Partner::pluck('nomePartner', 'idPartner'))
                    ->required()
                    ->searchable(),
                
                Select::make('idLocation')
                    ->label('Location (opzionale)')
                    ->options(Location::pluck('nomeLocation', 'idLocation'))
                    ->searchable(),
                
                DatePicker::make('data_da')
                    ->label('Dal')
                    ->required(),
                
                DatePicker::make('data_a')
                    ->label('Al')
                    ->required(),
                    
                Select::make('stato_pagamento')
                    ->label('Stato Pagamento Partner')
                    ->options([
                        'tutti' => 'Tutti',
                        'da_pagare' => 'Da Pagare (Debito Residuo)',
                        'pagati' => 'Già Pagati',
                    ])
                    ->default('tutti'),
            ])
            ->columns(5)
            ->statePath('data');
    }

    public function calcola()
    {
        $data = $this->form->getState();
        
        // Query per recuperare righe ordine con provvigioni partner
        // LEFT JOIN: many old tipologia_ingresso records were deleted in the legacy system
        $query = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->leftJoin('tipologia_ingresso', function ($join) {
                $join->on('tipologia_ingresso.idIngressoCont', '=', 'righe_ordine.idIngressoOrdine');
            })
            ->where('ordini.idPartnerOrdine', $data['idPartner'])
            ->where('ordini.statoOrdine', 2)
            ->whereDate('ordini.dataOrdine', '>=', $data['data_da'])
            ->whereDate('ordini.dataOrdine', '<=', $data['data_a']);
        
        // Filtro location opzionale
        if (!empty($data['idLocation'])) {
            $query->where('ordini.idLocationOrdine', $data['idLocation']);
        }
        
        // Filtro stato pagamento
        if (($data['stato_pagamento'] ?? 'tutti') === 'da_pagare') {
            $query->where(function($q) {
                $q->whereNull('ordini.dataPagamentoOrdinePartner')
                  ->orWhere('ordini.dataPagamentoOrdinePartner', '=', '')
                  ->orWhere('ordini.dataPagamentoOrdinePartner', '=', '0000-00-00 00:00:00');
            });
        } elseif (($data['stato_pagamento'] ?? 'tutti') === 'pagati') {
            $query->whereNotNull('ordini.dataPagamentoOrdinePartner')
                  ->where('ordini.dataPagamentoOrdinePartner', '!=', '')
                  ->where('ordini.dataPagamentoOrdinePartner', '!=', '0000-00-00 00:00:00');
        }
        
        $righe = $query->select([
                'ordini.idLocationOrdine',
                'righe_ordine.nomeIngressoOrdine',
                'righe_ordine.quantitaIngressoOrdine',
                DB::raw('COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0) as prezzoIngressoOrdine'),
                DB::raw('COALESCE(tipologia_ingresso.tipoProvvigionePartner, 0) as tipoProvvigionePartner'),
                DB::raw('COALESCE(tipologia_ingresso.valoreProvvigionePartner, 0) as valoreProvvigionePartner'),
                DB::raw('(righe_ordine.quantitaIngressoOrdine * COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0)) as venduto')
            ])
            ->get();

        $totaleVenduto = 0;
        $totaleProvvigione = 0;
        $totaleQuantita = 0;
        $dettagli = [];
        $locationCache = [];

        foreach ($righe as $riga) {
            $venduto = $riga->venduto;
            
            // Calcola provvigione partner
            if ($riga->tipoProvvigionePartner == 1) {
                // Percentuale
                $provvigione = ($venduto / 100) * $riga->valoreProvvigionePartner;
                $provvigioneLabel = $riga->valoreProvvigionePartner . '%';
            } else {
                // Fissa per biglietto
                $provvigione = $riga->valoreProvvigionePartner * $riga->quantitaIngressoOrdine;
                $provvigioneLabel = '€' . number_format($riga->valoreProvvigionePartner, 2);
            }
            
            $totaleVenduto += $venduto;
            $totaleProvvigione += $provvigione;
            $totaleQuantita += $riga->quantitaIngressoOrdine;
            
            // Cache location name
            if (!isset($locationCache[$riga->idLocationOrdine])) {
                $location = Location::find($riga->idLocationOrdine);
                $locationCache[$riga->idLocationOrdine] = $location ? $location->nomeLocation : 'N/A';
            }
            
            $dettagli[] = [
                'location' => $locationCache[$riga->idLocationOrdine],
                'biglietto' => $riga->nomeIngressoOrdine,
                'quantita' => $riga->quantitaIngressoOrdine,
                'prezzo' => $riga->prezzoIngressoOrdine,
                'venduto' => $venduto,
                'provvigione_label' => $provvigioneLabel,
                'provvigione' => $provvigione,
            ];
        }

        $partner = Partner::find($data['idPartner']);

        $this->risultati = [
            'partner' => $partner->nomePartner,
            'location' => !empty($data['idLocation']) ? Location::find($data['idLocation'])->nomeLocation : 'Tutte',
            'periodo' => date('d/m/Y', strtotime($data['data_da'])) . ' - ' . date('d/m/Y', strtotime($data['data_a'])),
            'dettagli' => $dettagli,
            'totaleQuantita' => $totaleQuantita,
            'totaleVenduto' => $totaleVenduto,
            'totaleProvvigione' => $totaleProvvigione,
        ];
    }
}
