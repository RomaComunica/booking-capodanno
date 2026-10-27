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
        $this->form->fill([
            'data_da' => now()->startOfYear(),
            'data_a' => now(),
            'stato_pagamento' => 'tutti',
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
                
                Select::make('periodo_speciale')
                    ->label('Periodo Festività')
                    ->placeholder('Seleziona periodo...')
                    ->options([
                        'tutto_anno' => "Tutto l'anno",
                        'capodanno' => 'Capodanno (1 Nov - 31 Dic)',
                        'halloween' => 'Halloween (1 Ott - 1 Nov)',
                        'san_valentino' => 'San Valentino (14 Gen - 14 Feb)',
                        'festa_donna' => 'Festa della Donna (8 Feb - 8 Mar)',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                        if (!$state) return;
                        $currentFrom = $get('data_da');
                        $year = $currentFrom ? date('Y', strtotime($currentFrom)) : date('Y');
                        
                        if ($state === 'tutto_anno') {
                            $set('data_da', "{$year}-01-01");
                            $set('data_a', "{$year}-12-31");
                        } elseif ($state === 'capodanno') {
                            $set('data_da', "{$year}-11-01");
                            $set('data_a', "{$year}-12-31");
                        } elseif ($state === 'halloween') {
                            $set('data_da', "{$year}-10-01");
                            $set('data_a', "{$year}-11-01");
                        } elseif ($state === 'san_valentino') {
                            $set('data_da', "{$year}-01-14");
                            $set('data_a', "{$year}-02-14");
                        } elseif ($state === 'festa_donna') {
                            $set('data_da', "{$year}-02-08");
                            $set('data_a', "{$year}-03-08");
                        }
                    }),

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
            ->columns(6)
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
            ->leftJoin('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->leftJoin('tipo_pagamenti', 'ordini.tipoPagamentoOrdine', '=', 'tipo_pagamenti.idTipoPagamento')
            ->where('ordini.idPartnerOrdine', $data['idPartner'])
            ->where('ordini.statoOrdine', 2)
            ->whereDate('ordini.dataOrdine', '>=', $data['data_da'])
            ->whereDate('ordini.dataOrdine', '<=', $data['data_a']);
        
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

        foreach ($righe as $riga) {
            $venduto = (float)$riga->venduto;
            
            // Calcola provvigione partner
            if ($riga->tipoProvvigionePartner == 1) {
                // Percentuale
                $provvigione = ($venduto / 100) * $riga->valoreProvvigionePartner;
                $provvigioneLabel = $riga->valoreProvvigionePartner . '%';
            } else {
                // Fissa per biglietto
                $provvigione = $riga->valoreProvvigionePartner * $riga->quantitaIngressoOrdine;
                $provvigioneLabel = '€' . number_format($riga->valoreProvvigionePartner, 2, ',', '.');
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

        $partner = Partner::find($data['idPartner']);

        $this->risultati = [
            'partner' => $partner ? $partner->nomePartner : 'N/D',
            'location' => !empty($data['idLocation']) ? (Location::find($data['idLocation'])->nomeLocation ?? 'Tutte') : 'Tutte',
            'periodo' => date('d/m/Y', strtotime($data['data_da'])) . ' - ' . date('d/m/Y', strtotime($data['data_a'])),
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
