<?php

namespace App\Filament\Pages;

use App\Models\Location;
use App\Models\Ordine;
use App\Models\RigaOrdine;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PagamentiLocation extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Pagamenti Location';

    protected static ?string $navigationGroup = 'Gestione';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.pagamenti-location';

    public ?array $data = [];

    // Location attualmente selezionata per il modal di dettaglio ordini
    public ?int $dettaglioLocationId = null;
    public ?string $dettaglioLocationNome = null;
    public array $dettaglioOrdini = [];

    // Checkbox selezione multipla location nella tabella principale
    public array $selectedLocations = [];

    // Checkbox selezione multipla ordini nel modal
    public array $selectedOrdini = [];

    // Filtro rapido tabella (tutte, solo_debiti, saldate)
    public string $filtroStato = 'solo_debiti';

    // Ricerca testuale per nome location
    public string $search = '';

    public function mount(): void
    {
        $this->form->fill([
            'data_da' => now()->startOfYear()->format('Y-m-d'),
            'data_a' => now()->format('Y-m-d'),
            'periodo_speciale' => 'tutto_anno',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->required()
                    ->live(),

                DatePicker::make('data_a')
                    ->label('Al')
                    ->required()
                    ->live(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    /**
     * Calcola i dati aggregati per tutte le location nel periodo selezionato.
     */
    public function getReportData(): array
    {
        $formData = $this->form->getState();
        $dataDa = !empty($formData['data_da']) ? $formData['data_da'] : now()->startOfYear()->format('Y-m-d');
        $dataA = !empty($formData['data_a']) ? $formData['data_a'] : now()->format('Y-m-d');

        // Estrai tutte le righe degli ordini completati (statoOrdine = 2) nel periodo
        $righe = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->leftJoin('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->leftJoin('tipo_pagamenti', 'ordini.tipoPagamentoOrdine', '=', 'tipo_pagamenti.idTipoPagamento')
            ->where('ordini.statoOrdine', 2)
            ->whereDate('ordini.dataOrdine', '>=', $dataDa)
            ->whereDate('ordini.dataOrdine', '<=', $dataA)
            ->select([
                'righe_ordine.idRigaOrdine',
                'righe_ordine.idIngressoOrdine',
                'righe_ordine.nomeIngressoOrdine',
                'righe_ordine.quantitaIngressoOrdine',
                DB::raw('COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0) as prezzoIngressoOrdine'),
                DB::raw('(righe_ordine.quantitaIngressoOrdine * COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0)) as venduto'),
                'ordini.numeroOrdine',
                'ordini.dataOrdine',
                'ordini.nomeClienteOrdine',
                'ordini.cognomeClienteOrdine',
                'ordini.dataPagamentoOrdineFornitore',
                'ordini.idLocationOrdine',
                'location.idLocation',
                'location.nomeLocation',
                'location.cittaLocation',
                'tipo_pagamenti.nomePagamento',
            ])
            ->get();

        if ($righe->isEmpty()) {
            return [
                'locations' => [],
                'totaleVenduto' => 0,
                'totaleDare' => 0,
                'totaleGiaPagato' => 0,
                'totaleDaPagareResiduo' => 0,
                'conteggioLocationDebito' => 0,
                'conteggioOrdiniDebito' => 0,
            ];
        }

        // Cache tipologie per calcolo provvigioni
        $idIngressoOrdini = $righe->pluck('idIngressoOrdine')->unique()->filter()->toArray();
        $ticketNames = $righe->pluck('nomeIngressoOrdine')->unique()->filter()->toArray();

        $tipologieByCont = DB::table('tipologia_ingresso')
            ->whereIn('idIngressoCont', $idIngressoOrdini)
            ->get()
            ->keyBy('idIngressoCont')
            ->toArray();

        $tipologieById = DB::table('tipologia_ingresso')
            ->whereIn('idIngresso', $idIngressoOrdini)
            ->get()
            ->keyBy('idIngresso')
            ->toArray();

        $tipologieByNameLoc = DB::table('tipologia_ingresso')
            ->whereIn('nomeIngresso', $ticketNames)
            ->get()
            ->keyBy(fn ($item) => trim($item->nomeIngresso) . '_' . $item->idLocation)
            ->toArray();

        $tipologieByName = DB::table('tipologia_ingresso')
            ->whereIn('nomeIngresso', $ticketNames)
            ->get()
            ->keyBy(fn ($item) => trim($item->nomeIngresso))
            ->toArray();

        // Raggruppa prima per ordine (un ordine può avere più righe)
        $ordiniMap = [];
        foreach ($righe as $riga) {
            $numOrd = (int) $riga->numeroOrdine;
            $venduto = (float) $riga->venduto;

            $locId = (int) ($riga->idLocationOrdine ?: $riga->idLocation ?: 0);

            $tipologia = $tipologieByCont[$riga->idIngressoOrdine]
                ?? $tipologieById[$riga->idIngressoOrdine]
                ?? ($tipologieByNameLoc[trim($riga->nomeIngressoOrdine) . '_' . $locId] ?? null)
                ?? ($tipologieByName[trim($riga->nomeIngressoOrdine)] ?? null);

            $tipoProvvigione = $tipologia ? (int) $tipologia->tipoProvvigione : 0;
            $valoreProvvigione = $tipologia ? (float) $tipologia->valoreProvvigione : 0.0;

            if ($tipoProvvigione == 1) {
                $provvigione = ($venduto / 100) * $valoreProvvigione;
            } else {
                $provvigione = $valoreProvvigione * (int) $riga->quantitaIngressoOrdine;
            }

            $dare = $venduto - $provvigione;

            $isPaid = !empty($riga->dataPagamentoOrdineFornitore)
                && $riga->dataPagamentoOrdineFornitore !== '0000-00-00 00:00:00'
                && $riga->dataPagamentoOrdineFornitore !== '';

            if (!isset($ordiniMap[$numOrd])) {
                $ordiniMap[$numOrd] = [
                    'numeroOrdine' => $numOrd,
                    'dataOrdine' => $riga->dataOrdine,
                    'cliente' => trim(($riga->nomeClienteOrdine ?? '') . ' ' . ($riga->cognomeClienteOrdine ?? '')),
                    'idLocation' => $locId,
                    'nomeLocation' => $riga->nomeLocation ?: 'Location #' . $locId,
                    'cittaLocation' => $riga->cittaLocation ?: '',
                    'metodoPagamento' => $riga->nomePagamento ?: 'Bonifico',
                    'venduto' => 0,
                    'provvigione' => 0,
                    'dare' => 0,
                    'isPaid' => $isPaid,
                    'biglietti' => 0,
                ];
            }

            $ordiniMap[$numOrd]['venduto'] += $venduto;
            $ordiniMap[$numOrd]['provvigione'] += $provvigione;
            $ordiniMap[$numOrd]['dare'] += $dare;
            $ordiniMap[$numOrd]['biglietti'] += (int) $riga->quantitaIngressoOrdine;
        }

        // Raggruppa ora per Location
        $locationsMap = [];
        $totaleVenduto = 0;
        $totaleDare = 0;
        $totaleGiaPagato = 0;
        $totaleDaPagareResiduo = 0;
        $ordiniDebitoSet = [];

        foreach ($ordiniMap as $numOrd => $ord) {
            $locId = $ord['idLocation'];

            if (!isset($locationsMap[$locId])) {
                $locationsMap[$locId] = [
                    'idLocation' => $locId,
                    'nomeLocation' => $ord['nomeLocation'],
                    'cittaLocation' => $ord['cittaLocation'],
                    'totaleOrdini' => 0,
                    'ordiniPagati' => 0,
                    'ordiniDaPagare' => 0,
                    'bigliettiTotali' => 0,
                    'totaleVenduto' => 0,
                    'totaleDare' => 0,
                    'totaleGiaPagato' => 0,
                    'totaleDaPagareResiduo' => 0,
                    'ordini' => [],
                ];
            }

            $locationsMap[$locId]['totaleOrdini']++;
            $locationsMap[$locId]['bigliettiTotali'] += $ord['biglietti'];
            $locationsMap[$locId]['totaleVenduto'] += $ord['venduto'];
            $locationsMap[$locId]['totaleDare'] += $ord['dare'];

            $totaleVenduto += $ord['venduto'];
            $totaleDare += $ord['dare'];

            if ($ord['isPaid']) {
                $locationsMap[$locId]['ordiniPagati']++;
                $locationsMap[$locId]['totaleGiaPagato'] += $ord['dare'];
                $totaleGiaPagato += $ord['dare'];
            } else {
                $locationsMap[$locId]['ordiniDaPagare']++;
                $locationsMap[$locId]['totaleDaPagareResiduo'] += $ord['dare'];
                $totaleDaPagareResiduo += $ord['dare'];
                $ordiniDebitoSet[$numOrd] = true;
            }

            $locationsMap[$locId]['ordini'][] = $ord;
        }

        // Filtra e ordina per residuo da pagare discendente
        $searchLower = mb_strtolower(trim($this->search));
        $filteredLocations = array_filter($locationsMap, function ($loc) use ($searchLower) {
            if ($searchLower !== '') {
                $name = mb_strtolower($loc['nomeLocation'] . ' ' . $loc['cittaLocation']);
                if (!str_contains($name, $searchLower)) {
                    return false;
                }
            }

            if ($this->filtroStato === 'solo_debiti') {
                return $loc['totaleDaPagareResiduo'] > 0.01;
            }

            if ($this->filtroStato === 'saldate') {
                return $loc['totaleDaPagareResiduo'] <= 0.01 && $loc['totaleDare'] > 0;
            }

            return true;
        });

        uasort($filteredLocations, function ($a, $b) {
            // Prima chi ha debito maggiore
            if ($b['totaleDaPagareResiduo'] != $a['totaleDaPagareResiduo']) {
                return $b['totaleDaPagareResiduo'] <=> $a['totaleDaPagareResiduo'];
            }
            return strcmp($a['nomeLocation'], $b['nomeLocation']);
        });

        $conteggioLocationDebito = count(array_filter($locationsMap, fn ($loc) => $loc['totaleDaPagareResiduo'] > 0.01));

        return [
            'locations' => $filteredLocations,
            'totaleVenduto' => $totaleVenduto,
            'totaleDare' => $totaleDare,
            'totaleGiaPagato' => $totaleGiaPagato,
            'totaleDaPagareResiduo' => $totaleDaPagareResiduo,
            'conteggioLocationDebito' => $conteggioLocationDebito,
            'conteggioOrdiniDebito' => count($ordiniDebitoSet),
        ];
    }

    /**
     * Salda tutti gli ordini arretrati di una singola location.
     */
    public function saldaTuttiOrdiniLocation(int $idLocation): void
    {
        $report = $this->getReportData();
        $loc = $report['locations'][$idLocation] ?? null;

        if (!$loc) {
            return;
        }

        $ordiniDaSaldare = array_filter($loc['ordini'], fn ($o) => !$o['isPaid']);
        $numeriOrdini = array_column($ordiniDaSaldare, 'numeroOrdine');

        if (!empty($numeriOrdini)) {
            Ordine::whereIn('numeroOrdine', $numeriOrdini)->update([
                'dataPagamentoOrdineFornitore' => now(),
            ]);

            Notification::make()
                ->title('Pagamento Registrato!')
                ->body('Saldati ' . count($numeriOrdini) . ' ordini per ' . $loc['nomeLocation'] . ' (€ ' . number_format($loc['totaleDaPagareResiduo'], 2, ',', '.') . ')')
                ->success()
                ->send();
        }

        // Se era aperto il modal di dettaglio, rinfrescalo
        if ($this->dettaglioLocationId === $idLocation) {
            $this->apriDettaglioOrdini($idLocation);
        }
    }

    /**
     * Salda tutti gli ordini delle location selezionate tramite checkbox.
     */
    public function saldaLocationSelezionate(): void
    {
        if (empty($this->selectedLocations)) {
            Notification::make()
                ->title('Nessuna location selezionata')
                ->warning()
                ->send();
            return;
        }

        $report = $this->getReportData();
        $totOrdini = 0;
        $totImporto = 0;

        foreach ($this->selectedLocations as $idLoc => $selected) {
            if (!$selected) continue;

            $loc = $report['locations'][$idLoc] ?? null;
            if (!$loc) continue;

            $ordiniDaSaldare = array_filter($loc['ordini'], fn ($o) => !$o['isPaid']);
            $numeriOrdini = array_column($ordiniDaSaldare, 'numeroOrdine');

            if (!empty($numeriOrdini)) {
                Ordine::whereIn('numeroOrdine', $numeriOrdini)->update([
                    'dataPagamentoOrdineFornitore' => now(),
                ]);
                $totOrdini += count($numeriOrdini);
                $totImporto += $loc['totaleDaPagareResiduo'];
            }
        }

        $this->selectedLocations = [];

        Notification::make()
            ->title('Pagamenti in blocco registrati!')
            ->body("Saldati {$totOrdini} ordini complessivi per un totale di € " . number_format($totImporto, 2, ',', '.'))
            ->success()
            ->send();
    }

    /**
     * Apre il modal di dettaglio con la lista degli ordini di una specifica location.
     */
    public function apriDettaglioOrdini(int $idLocation): void
    {
        $report = $this->getReportData();
        $loc = $report['locations'][$idLocation] ?? null;

        if (!$loc) {
            return;
        }

        $this->dettaglioLocationId = $idLocation;
        $this->dettaglioLocationNome = $loc['nomeLocation'];
        $this->dettaglioOrdini = $loc['ordini'];
        $this->selectedOrdini = [];
    }

    /**
     * Chiude il modal di dettaglio.
     */
    public function chiudiDettaglioOrdini(): void
    {
        $this->dettaglioLocationId = null;
        $this->dettaglioLocationNome = null;
        $this->dettaglioOrdini = [];
        $this->selectedOrdini = [];
    }

    /**
     * Toggle pagamento singolo ordine direttamente dal modal o dalla lista.
     */
    public function togglePagamentoOrdine(int $numeroOrdine): void
    {
        $ordine = Ordine::find($numeroOrdine);
        if (!$ordine) return;

        $isPaid = !empty($ordine->dataPagamentoOrdineFornitore) && $ordine->dataPagamentoOrdineFornitore !== '0000-00-00 00:00:00';
        $ordine->update([
            'dataPagamentoOrdineFornitore' => $isPaid ? null : now(),
        ]);

        if ($this->dettaglioLocationId) {
            $this->apriDettaglioOrdini($this->dettaglioLocationId);
        }

        Notification::make()
            ->title($isPaid ? "Ordine #{$numeroOrdine}: Pagamento annullato (Da pagare)" : "Ordine #{$numeroOrdine}: Pagamento registrato (Pagato)")
            ->success()
            ->send();
    }

    /**
     * Salda solo gli ordini selezionati con checkbox dentro il modal di dettaglio.
     */
    public function saldaOrdiniSelezionati(): void
    {
        $numeriOrdini = [];
        foreach ($this->selectedOrdini as $num => $selected) {
            if ($selected) {
                $numeriOrdini[] = (int) $num;
            }
        }

        if (empty($numeriOrdini)) {
            Notification::make()
                ->title('Nessun ordine selezionato')
                ->warning()
                ->send();
            return;
        }

        Ordine::whereIn('numeroOrdine', $numeriOrdini)->update([
            'dataPagamentoOrdineFornitore' => now(),
        ]);

        $this->selectedOrdini = [];

        if ($this->dettaglioLocationId) {
            $this->apriDettaglioOrdini($this->dettaglioLocationId);
        }

        Notification::make()
            ->title('Ordini saldati!')
            ->body('Aggiornati con successo ' . count($numeriOrdini) . ' ordini.')
            ->success()
            ->send();
    }

    /**
     * Seleziona / deseleziona tutti gli ordini non pagati nel modal.
     */
    public function toggleSelectAllOrdini(bool $select): void
    {
        $this->selectedOrdini = [];
        if ($select) {
            foreach ($this->dettaglioOrdini as $ord) {
                if (!$ord['isPaid']) {
                    $this->selectedOrdini[$ord['numeroOrdine']] = true;
                }
            }
        }
    }

    /**
     * Seleziona / deseleziona tutte le location con debito nella vista principale.
     */
    public function toggleSelectAllLocations(bool $select): void
    {
        $this->selectedLocations = [];
        if ($select) {
            $report = $this->getReportData();
            foreach ($report['locations'] as $idLoc => $loc) {
                if ($loc['totaleDaPagareResiduo'] > 0.01) {
                    $this->selectedLocations[$idLoc] = true;
                }
            }
        }
    }
}
