<?php

namespace App\Filament\Pages;

use App\Models\Partner;
use App\Models\Location;
use App\Models\Ordine;
use App\Models\RigaOrdine;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
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
        $defaultYear = \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $defaultPeriod = \App\Services\StatisticsService::getDefaultDashboardPeriod();
        $range = \App\Services\StatisticsService::resolvePeriodRange($defaultYear, $defaultPeriod);

        $this->form->fill([
            'anno' => (string) $defaultYear,
            'periodo_speciale' => $defaultPeriod,
            'data_da' => $range['from_date'],
            'data_a' => $range['to_date'],
            'stato_pagamento' => 'tutti',
        ]);
    }

    public function form(Form $form): Form
    {
        $defaultYear = \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $defaultPeriod = \App\Services\StatisticsService::getDefaultDashboardPeriod();

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

                Select::make('stato_pagamento')
                    ->label('Stato Pagamento Partner')
                    ->options([
                        'tutti' => 'Tutti',
                        'da_pagare' => 'Da Pagare (Debito Residuo)',
                        'pagati' => 'Già Pagati',
                    ])
                    ->default('tutti'),

                Select::make('anno')
                    ->label('Anno')
                    ->options(function () {
                        $years = [];
                        for ($y = date('Y') + 1; $y >= 2017; $y--) {
                            $years[(string) $y] = $y;
                        }
                        return $years;
                    })
                    ->default((string) $defaultYear)
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                        $period = $get('periodo_speciale') ?: 'tutto_anno';
                        if ($period !== 'personalizzato') {
                            $range = \App\Services\StatisticsService::resolvePeriodRange($state, $period);
                            $set('data_da', $range['from_date']);
                            $set('data_a', $range['to_date']);
                        }
                    }),
                
                Select::make('periodo_speciale')
                    ->label('Periodo Festività')
                    ->options([
                        'tutto_anno' => "Tutto l'anno (1 Gen - 31 Dic)",
                        'capodanno' => 'Capodanno (1 Nov - 31 Dic)',
                        'halloween' => 'Halloween (1 Ott - 1 Nov)',
                        'san_valentino' => 'San Valentino (14 Gen - 14 Feb)',
                        'festa_donna' => 'Festa della Donna (8 Feb - 8 Mar)',
                        'personalizzato' => 'Periodo personalizzato (Dal / Al)',
                    ])
                    ->default($defaultPeriod)
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                        $year = $get('anno') ?: \App\Models\Setting::getVal('anno_riferimento', date('Y'));
                        if ($state !== 'personalizzato') {
                            $range = \App\Services\StatisticsService::resolvePeriodRange($year, $state);
                            $set('data_da', $range['from_date']);
                            $set('data_a', $range['to_date']);
                        }
                    }),

                DatePicker::make('data_da')
                    ->label('Dal')
                    ->visible(fn (\Filament\Forms\Get $get): bool => $get('periodo_speciale') === 'personalizzato')
                    ->required(fn (\Filament\Forms\Get $get): bool => $get('periodo_speciale') === 'personalizzato'),
                
                DatePicker::make('data_a')
                    ->label('Al')
                    ->visible(fn (\Filament\Forms\Get $get): bool => $get('periodo_speciale') === 'personalizzato')
                    ->required(fn (\Filament\Forms\Get $get): bool => $get('periodo_speciale') === 'personalizzato'),
            ])
            ->columns([
                'default' => 1,
                'sm' => 2,
                'lg' => 3,
            ])
            ->statePath('data');
    }

    public function calcola()
    {
        $data = $this->form->getState();

        $range = \App\Services\StatisticsService::resolvePeriodRange(
            $data['anno'] ?? \App\Models\Setting::getVal('anno_riferimento', date('Y')),
            $data['periodo_speciale'] ?? 'tutto_anno',
            $data['data_da'] ?? null,
            $data['data_a'] ?? null
        );
        $dataDa = $range['from_date'];
        $dataA = $range['to_date'];
        
        // Query per recuperare righe ordine con provvigioni partner
        // LEFT JOIN: many old tipologia_ingresso records were deleted in the legacy system
        $query = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->leftJoin('tipologia_ingresso', function ($join) {
                $join->on('tipologia_ingresso.idIngressoCont', '=', 'righe_ordine.idIngressoOrdine');
            })
            ->leftJoin('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->leftJoin('tipo_pagamenti', 'ordini.tipoPagamentoOrdine', '=', 'tipo_pagamenti.idTipoPagamento')
            ->where('ordini.idPartnerOrdine', $data['idPartner'])
            ->where('ordini.statoOrdine', 2)
            ->whereDate('ordini.dataOrdine', '>=', $dataDa)
            ->whereDate('ordini.dataOrdine', '<=', $dataA);
        
        // Filtro location opzionale
        if (!empty($data['idLocation'])) {
            $query->where('ordini.idLocationOrdine', $data['idLocation']);
        }
        
        $righe = $query->select([
                'ordini.numeroOrdine',
                'ordini.nomeClienteOrdine',
                'ordini.cognomeClienteOrdine',
                'ordini.dataPagamentoOrdinePartner',
                'ordini.idLocationOrdine',
                'location.nomeLocation',
                'tipo_pagamenti.nomePagamento as metodo_pagamento',
                'righe_ordine.nomeIngressoOrdine',
                'righe_ordine.quantitaIngressoOrdine',
                DB::raw('COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0) as prezzoIngressoOrdine'),
                DB::raw('COALESCE(tipologia_ingresso.tipoProvvigionePartner, 0) as tipoProvvigionePartner'),
                DB::raw('COALESCE(tipologia_ingresso.valoreProvvigionePartner, 0) as valoreProvvigionePartner'),
                DB::raw('(righe_ordine.quantitaIngressoOrdine * COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0)) as venduto')
            ])
            ->orderBy('ordini.numeroOrdine', 'desc')
            ->get();

        $totaleVenduto = 0;
        $totaleProvvigione = 0;
        $totaleGiaPagato = 0;
        $totaleQuantita = 0;
        $dettagli = [];
        $statoPagamento = $data['stato_pagamento'] ?? 'tutti';
        $partner = Partner::find($data['idPartner']);
        $tipoPartner = $partner?->tipoPartner ?? Partner::TIPO_DIRETTO;
        $partnerPrendeProvvigione = Partner::tipoPrendeProvvigione($tipoPartner);

        foreach ($righe as $riga) {
            $venduto = (float)$riga->venduto;
            
            $provvigione = Partner::calcolaImportoProvvigione(
                $tipoPartner,
                $venduto,
                (int) $riga->quantitaIngressoOrdine,
                $riga->tipoProvvigionePartner,
                $riga->valoreProvvigionePartner
            );

            if (!$partnerPrendeProvvigione) {
                $provvigioneLabel = 'Diretto';
            } elseif ($riga->tipoProvvigionePartner == 1) {
                $provvigioneLabel = $riga->valoreProvvigionePartner . '%';
            } else {
                $provvigioneLabel = '€' . number_format((float) $riga->valoreProvvigionePartner, 2, ',', '.');
            }
            
            $isPaid = !empty($riga->dataPagamentoOrdinePartner) 
                && $riga->dataPagamentoOrdinePartner !== '0000-00-00 00:00:00' 
                && $riga->dataPagamentoOrdinePartner !== '';

            // Totali finanziari complessivi del periodo e partner
            $totaleVenduto += $venduto;
            $totaleProvvigione += $provvigione;
            $totaleQuantita += $riga->quantitaIngressoOrdine;
            
            if ($isPaid) {
                $totaleGiaPagato += $provvigione;
            }

            // Filtro visualizzazione tabella ordini
            if ($statoPagamento === 'da_pagare' && $isPaid) {
                continue;
            }
            if ($statoPagamento === 'pagati' && !$isPaid) {
                continue;
            }
            
            $dettagli[] = [
                'numeroOrdine' => $riga->numeroOrdine,
                'cliente' => trim(($riga->nomeClienteOrdine ?? '') . ' ' . ($riga->cognomeClienteOrdine ?? '')),
                'location' => $riga->nomeLocation ?? 'N/A',
                'metodo_pagamento' => $riga->metodo_pagamento ?? 'Non spec.',
                'biglietto' => $riga->nomeIngressoOrdine,
                'quantita' => $riga->quantitaIngressoOrdine,
                'prezzo' => $riga->prezzoIngressoOrdine,
                'venduto' => $venduto,
                'provvigione_label' => $provvigioneLabel,
                'provvigione' => $provvigione,
                'is_pagato_partner' => $isPaid,
            ];
        }

        $totaleDaPagareResiduo = max(0, $totaleProvvigione - $totaleGiaPagato);

        $this->risultati = [
            'partner' => $partner ? $partner->nomePartner : 'N/D',
            'tipo_partner' => Partner::labelTipo($tipoPartner),
            'prende_provvigione' => $partnerPrendeProvvigione,
            'location' => !empty($data['idLocation']) ? (Location::find($data['idLocation'])->nomeLocation ?? 'Tutte') : 'Tutte',
            'periodo' => date('d/m/Y', strtotime($dataDa)) . ' - ' . date('d/m/Y', strtotime($dataA)),
            'dettagli' => $dettagli,
            'totaleQuantita' => $totaleQuantita,
            'totaleVenduto' => $totaleVenduto,
            'totaleProvvigione' => $totaleProvvigione,
            'totaleGiaPagato' => $totaleGiaPagato,
            'totaleDaPagareResiduo' => $totaleDaPagareResiduo,
        ];
    }

    public function togglePagamentoPartner(int $numeroOrdine): void
    {
        $ordine = Ordine::find($numeroOrdine);
        if ($ordine) {
            $isPaid = !empty($ordine->dataPagamentoOrdinePartner) && $ordine->dataPagamentoOrdinePartner !== '0000-00-00 00:00:00';
            $ordine->update([
                'dataPagamentoOrdinePartner' => $isPaid ? null : now(),
            ]);
            
            $this->calcola();
            
            Notification::make()
                ->title($isPaid ? 'Ordine #' . $numeroOrdine . ': Pagamento Partner rimosso (Da Pagare)' : 'Ordine #' . $numeroOrdine . ': Pagamento Partner registrato (PAGATO)')
                ->success()
                ->send();
        }
    }
}
