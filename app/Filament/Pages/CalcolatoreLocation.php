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
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CalcolatoreLocation extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationLabel = 'Calcolatore Location';
    
    protected static ?string $navigationGroup = 'Gestione';

    protected static string $view = 'filament.pages.calcolatore-location';

    public ?array $data = [];
    
    public ?array $risultati = null;

    // Array matching row index to assigned table name
    public array $tavoli = [];

    // Filtro per visualizzare gli ordini di un singolo biglietto
    public ?string $filtroBiglietto = null;

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
                Select::make('citta')
                    ->label('Città')
                    ->placeholder('Tutte le Città')
                    ->options(fn () => Location::getAvailableCities())
                    ->live()
                    ->afterStateUpdated(fn (\Filament\Forms\Set $set) => $set('idLocation', null)),

                Select::make('idLocation')
                    ->label('Location')
                    ->placeholder('Tutte le Location')
                    ->options(function (\Filament\Forms\Get $get) {
                        $citta = $get('citta');
                        $query = Location::orderBy('nomeLocation');
                        if (!empty($citta)) {
                            if (strtolower($citta) === 'roma') {
                                $query->where(function ($q) {
                                    $q->where('cittaLocation', 'Roma')
                                      ->orWhereNull('cittaLocation')
                                      ->orWhere('cittaLocation', '');
                                });
                            } else {
                                $query->where('cittaLocation', $citta);
                            }
                        }
                        return $query->pluck('nomeLocation', 'idLocation');
                    })
                    ->searchable(),

                Select::make('stato_pagamento')
                    ->label('Stato Pagamento Location')
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
        $this->filtroBiglietto = null;
        $data = $this->form->getState();

        $range = \App\Services\StatisticsService::resolvePeriodRange(
            $data['anno'] ?? \App\Models\Setting::getVal('anno_riferimento', date('Y')),
            $data['periodo_speciale'] ?? 'tutto_anno',
            $data['data_da'] ?? null,
            $data['data_a'] ?? null
        );
        $dataDa = $range['from_date'];
        $dataA = $range['to_date'];
        
        $query = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->leftJoin('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->leftJoin('tipo_pagamenti', 'ordini.tipoPagamentoOrdine', '=', 'tipo_pagamenti.idTipoPagamento')
            ->where('ordini.statoOrdine', 2)
            ->whereDate('ordini.dataOrdine', '>=', $dataDa)
            ->whereDate('ordini.dataOrdine', '<=', $dataA);

        if (!empty($data['citta'])) {
            $citta = $data['citta'];
            if (strtolower($citta) === 'roma') {
                $query->where(function ($q) {
                    $q->where('location.cittaLocation', 'Roma')
                      ->orWhereNull('location.cittaLocation')
                      ->orWhere('location.cittaLocation', '');
                });
            } else {
                $query->where('location.cittaLocation', $citta);
            }
        }

        if (!empty($data['idLocation'])) {
            $query->where('ordini.idLocationOrdine', $data['idLocation']);
        }
            
        $righe = $query->select([
                'righe_ordine.idRigaOrdine',
                'righe_ordine.idIngressoOrdine',
                'righe_ordine.nomeIngressoOrdine',
                'righe_ordine.quantitaIngressoOrdine',
                DB::raw('COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0) as prezzoIngressoOrdine'),
                DB::raw('(righe_ordine.quantitaIngressoOrdine * COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0)) as venduto'),
                'ordini.nomeClienteOrdine',
                'ordini.cognomeClienteOrdine',
                'ordini.noteOrdine',
                'ordini.numeroOrdine',
                'ordini.tavolo',
                'ordini.dataPagamentoOrdineFornitore',
                'location.nomeLocation',
                'location.idLocation',
                'tipo_pagamenti.nomePagamento',
            ])
            ->orderBy('ordini.dataOrdine', 'desc')
            ->get();

        // Load tipologie in memory to prevent duplicate rows with robust multi-tier fallback
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
            ->keyBy(function ($item) {
                return trim($item->nomeIngresso) . '_' . $item->idLocation;
            })
            ->toArray();

        $tipologieByName = DB::table('tipologia_ingresso')
            ->whereIn('nomeIngresso', $ticketNames)
            ->get()
            ->keyBy(function ($item) {
                return trim($item->nomeIngresso);
            })
            ->toArray();

        $totaleVenduto = 0;
        $totaleProvvigioni = 0;
        $totaleDare = 0;
        $totaleGiaPagato = 0;
        $totaleDaPagareResiduo = 0;
        $totaleQuantita = 0;
        $dettagli = [];
        
        // Reset tavoli array
        $this->tavoli = [];
        $statoPagamento = $data['stato_pagamento'] ?? 'tutti';

        foreach ($righe as $riga) {
            $venduto = (float)$riga->venduto;
            
            $tipologia = $tipologieByCont[$riga->idIngressoOrdine]
                ?? $tipologieById[$riga->idIngressoOrdine]
                ?? ($tipologieByNameLoc[trim($riga->nomeIngressoOrdine) . '_' . $riga->idLocation] ?? null)
                ?? ($tipologieByName[trim($riga->nomeIngressoOrdine)] ?? null);

            $tipoProvvigione = $tipologia ? (int)$tipologia->tipoProvvigione : 0;
            $valoreProvvigione = $tipologia ? (float)$tipologia->valoreProvvigione : 0.0;
            
            // Calcola provvigione (nostro guadagno)
            if ($tipoProvvigione == 1) {
                // Percentuale
                $provvigione = ($venduto / 100) * $valoreProvvigione;
            } else {
                // Fissa per biglietto
                $provvigione = $valoreProvvigione * (int)$riga->quantitaIngressoOrdine;
            }
            
            // Quota spettante alla location
            $dare = $venduto - $provvigione;
            
            $isPaid = !empty($riga->dataPagamentoOrdineFornitore) 
                && $riga->dataPagamentoOrdineFornitore !== '0000-00-00 00:00:00'
                && $riga->dataPagamentoOrdineFornitore !== '';

            // Totali finanziari complessivi del periodo e location (mostrano sempre il quadro completo)
            $totaleVenduto += $venduto;
            $totaleProvvigioni += $provvigione;
            $totaleDare += $dare;
            $totaleQuantita += $riga->quantitaIngressoOrdine;

            if ($isPaid) {
                $totaleGiaPagato += $dare;
            } else {
                $totaleDaPagareResiduo += $dare;
            }

            // Filtro visualizzazione tabella ordini
            if ($statoPagamento === 'da_pagare' && $isPaid) {
                continue;
            }
            if ($statoPagamento === 'pagati' && !$isPaid) {
                continue;
            }
            
            $dettagliIndex = count($dettagli);
            $tavoloAssegnato = $riga->tavolo ?: $this->extractTavoloName($riga->noteOrdine, (int)$riga->numeroOrdine);
            $dettagli[] = [
                'index' => $dettagliIndex,
                'cliente' => $riga->nomeClienteOrdine . ' ' . $riga->cognomeClienteOrdine,
                'nome' => $riga->nomeClienteOrdine,
                'cognome' => $riga->cognomeClienteOrdine,
                'location' => $riga->nomeLocation ?: 'Location #' . $riga->idLocation,
                'biglietto' => $riga->nomeIngressoOrdine,
                'quantita' => $riga->quantitaIngressoOrdine,
                'prezzo' => (float)$riga->prezzoIngressoOrdine,
                'venduto' => $venduto,
                'provvigione' => $provvigione,
                'dare' => $dare,
                'metodo_pagamento' => $riga->nomePagamento ?: 'Bonifico Bancario',
                'is_pagata_location' => $isPaid,
                'data_pagamento_location' => $riga->dataPagamentoOrdineFornitore,
                'note' => $riga->noteOrdine,
                'numeroOrdine' => $riga->numeroOrdine,
                'tavolo' => $tavoloAssegnato,
                'tavolo_manuale' => $riga->tavolo,
            ];

            // Pre-fill tavoli using database or note extraction fallback
            $this->tavoli[$dettagliIndex] = $tavoloAssegnato;
        }

        $locationName = !empty($data['idLocation']) 
            ? (Location::find($data['idLocation'])?->nomeLocation ?? 'Location #' . $data['idLocation']) 
            : 'Tutte le Location';

        $this->risultati = [
            'location' => $locationName,
            'is_tutte_le_location' => empty($data['idLocation']),
            'periodo' => date('d/m/Y', strtotime($dataDa)) . ' - ' . date('d/m/Y', strtotime($dataA)),
            'dettagli' => $dettagli,
            'totaleQuantita' => $totaleQuantita,
            'totaleVenduto' => $totaleVenduto,
            'totaleProvvigioni' => $totaleProvvigioni,
            'totaleDare' => $totaleDare,
            'totaleGiaPagato' => $totaleGiaPagato,
            'totaleDaPagareResiduo' => $totaleDaPagareResiduo,
        ];
    }

    public function togglePagamentoLocation(int $numeroOrdine): void
    {
        $ordine = Ordine::find($numeroOrdine);
        if ($ordine) {
            $isPaid = !empty($ordine->dataPagamentoOrdineFornitore) && $ordine->dataPagamentoOrdineFornitore !== '0000-00-00 00:00:00';
            $ordine->update([
                'dataPagamentoOrdineFornitore' => $isPaid ? null : now(),
            ]);
            
            $this->calcola();
            
            Notification::make()
                ->title($isPaid ? 'Ordine #' . $numeroOrdine . ': Pagamento Location rimosso (Non Pagata)' : 'Ordine #' . $numeroOrdine . ': Pagamento Location registrato (PAGATO)')
                ->success()
                ->send();
        }
    }

    public function filtraPerBiglietto(?string $biglietto): void
    {
        if ($this->filtroBiglietto === $biglietto) {
            $this->filtroBiglietto = null;
        } else {
            $this->filtroBiglietto = $biglietto;
        }
    }

    public function resetFiltroBiglietto(): void
    {
        $this->filtroBiglietto = null;
    }

    public function getIngressiAggregati(): array
    {
        if (!$this->risultati || empty($this->risultati['dettagli'])) {
            return [];
        }

        $aggregati = [];
        $isTutteLeLocation = (bool)($this->risultati['is_tutte_le_location'] ?? false);

        foreach ($this->risultati['dettagli'] as $d) {
            $loc = $d['location'] ?? '';
            $nome = trim($d['biglietto'] ?? '');
            $prezzo = (float)($d['prezzo'] ?? 0);

            // Se sono selezionate tutte le location, discriminiamo per location; altrimenti solo per nome biglietto e prezzo
            $key = ($isTutteLeLocation ? $loc . '|' : '') . $nome . '|' . number_format($prezzo, 2, '.', '');

            if (!isset($aggregati[$key])) {
                $aggregati[$key] = [
                    'biglietto' => $nome,
                    'location' => $loc,
                    'prezzo' => $prezzo,
                    'quantita' => 0,
                    'venduto' => 0.0,
                    'provvigione' => 0.0,
                    'dare' => 0.0,
                    'ordini_count' => 0,
                ];
            }

            $aggregati[$key]['quantita'] += (int)($d['quantita'] ?? 0);
            $aggregati[$key]['venduto'] += (float)($d['venduto'] ?? 0);
            $aggregati[$key]['provvigione'] += (float)($d['provvigione'] ?? 0);
            $aggregati[$key]['dare'] += (float)($d['dare'] ?? 0);
            $aggregati[$key]['ordini_count']++;
        }

        foreach ($aggregati as &$item) {
            $item['utile_unitario'] = $item['quantita'] > 0
                ? ($item['provvigione'] / $item['quantita'])
                : 0.0;
        }
        unset($item);

        uasort($aggregati, function ($a, $b) {
            if ($b['quantita'] === $a['quantita']) {
                return $b['venduto'] <=> $a['venduto'];
            }
            return $b['quantita'] <=> $a['quantita'];
        });

        return array_values($aggregati);
    }

    private function isBlacklistedTableWord(string $word): bool
    {
        $word = strtolower(trim($word));
        $blacklist = [
            'vegano', 'vegani', 'pesce', 'carne', 'glutine', 'celiaco', 'ciliaco', 
            'intollerante', 'intolleranze', 'allergico', 'allergie', 'bambini', 
            'bambino', 'seggiolone', 'persone', 'persona', 'posto', 'posti', 
            'favore', 'grazie', 'prego', 'richiesta', 'da', 'di', 'con', 'per',
            'cena', 'cenone', 'servita', 'tavolo', 'tavoli', 'ingresso', 'ingressi',
            'menu', 'menù', 'buffet', 'salato', 'dolce', 'bottiglia', 'bottiglie'
        ];
        
        if (in_array($word, $blacklist)) {
            return true;
        }
        
        if (preg_match('/^(?:da|di|per)?\s*\d+\s*(?:persone|posti|pax|adulti|bambini)?$/i', $word)) {
            return true;
        }
        
        return false;
    }

    private function noteHasTableRequest(?string $note): bool
    {
        if (empty($note)) return false;
        $extracted = $this->extractTavoloName($note);
        return !empty($extracted);
    }

    private function tokenizeKey(string $key): array
    {
        $key = strtolower(trim($key));
        if (empty($key)) return [];
        
        if (is_numeric($key)) {
            return [$key];
        }
        
        // Separiamo per spazi o punteggiatura comune
        $words = preg_split('/[\s,\.\-\'\"]+/', $key);
        $tokens = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) >= 3 && !$this->isBlacklistedTableWord($word)) {
                $tokens[] = $word;
            }
        }
        
        return $tokens;
    }

    public function getTavoliAggregati(): array
    {
        if (!$this->risultati) return [];

        $dettagli = $this->risultati['dettagli'];
        $groups = [];

        // 1. Inizializza i gruppi con le righe che hanno richieste esplicite o tavoli assegnati
        foreach ($dettagli as $dettaglio) {
            $idx = $dettaglio['index'];
            $tavolo = trim($this->tavoli[$idx] ?? '');
            
            $hasRequest = !empty($dettaglio['tavolo_manuale']) || $this->noteHasTableRequest($dettaglio['note']);
            
            if ($hasRequest || !empty($tavolo)) {
                $keys = [(string)$dettaglio['numeroOrdine']];
                if (!empty($dettaglio['cognome'])) {
                    $keys = array_merge($keys, $this->tokenizeKey($dettaglio['cognome']));
                }
                if (!empty($tavolo)) {
                    $keys = array_merge($keys, $this->tokenizeKey($tavolo));
                }
                
                $groups[] = [
                    'orders' => [$dettaglio],
                    'keys' => array_values(array_unique(array_filter($keys)))
                ];
            }
        }

        // 2. Unisci ricorsivamente i gruppi che condividono chiavi/token comuni (transitive links)
        $merged = true;
        while ($merged) {
            $merged = false;
            for ($i = 0; $i < count($groups); $i++) {
                for ($j = $i + 1; $j < count($groups); $j++) {
                    $intersect = array_intersect($groups[$i]['keys'], $groups[$j]['keys']);
                    if (!empty($intersect)) {
                        $groups[$i]['orders'] = array_merge($groups[$i]['orders'], $groups[$j]['orders']);
                        $groups[$i]['keys'] = array_values(array_unique(array_merge($groups[$i]['keys'], $groups[$j]['keys'])));
                        array_splice($groups, $j, 1);
                        $merged = true;
                        break 2;
                    }
                }
            }
        }

        // 3. Associa ordini senza richiesta esplicita che però appartengono a chiavi esistenti (es. l'originale Corridore)
        foreach ($dettagli as $dettaglio) {
            $alreadyGrouped = false;
            foreach ($groups as $gVal) {
                foreach ($gVal['orders'] as $gOrd) {
                    if ($gOrd['numeroOrdine'] === $dettaglio['numeroOrdine']) {
                        $alreadyGrouped = true;
                        break 2;
                    }
                }
            }

            if (!$alreadyGrouped) {
                $searchTokens = [(string)$dettaglio['numeroOrdine']];
                if (!empty($dettaglio['cognome'])) {
                    $searchTokens = array_merge($searchTokens, $this->tokenizeKey($dettaglio['cognome']));
                }
                $searchTokens = array_values(array_unique(array_filter($searchTokens)));

                foreach ($groups as &$gRef) {
                    $intersect = array_intersect($searchTokens, $gRef['keys']);
                    if (!empty($intersect)) {
                        $gRef['orders'][] = $dettaglio;
                        $gRef['keys'] = array_values(array_unique(array_merge($gRef['keys'], $searchTokens)));
                        break;
                    }
                }
                unset($gRef);
            }
        }

        // 4. Formatta il risultato finale per ciascun gruppo
        $formattedGroups = [];
        foreach ($groups as $group) {
            $clientiList = [];
            $totalePersone = 0;
            $totaleVoucher = 0;
            $orderProcessed = [];

            // Determina il nome ottimale per il tavolo
            $nameCandidate = '';
            foreach ($group['orders'] as $ord) {
                $t = trim($this->tavoli[$ord['index']] ?? '');
                if (!empty($t) && !is_numeric($t) && !$this->isBlacklistedTableWord($t)) {
                    $nameCandidate = $t;
                    break;
                }
            }

            if (empty($nameCandidate)) {
                $surnames = [];
                foreach ($group['orders'] as $ord) {
                    if (!empty($ord['cognome'])) {
                        $surnames[] = ucfirst(strtolower($ord['cognome']));
                    }
                }
                $surnames = array_unique($surnames);
                $nameCandidate = !empty($surnames) ? implode(' / ', $surnames) : 'Tavolo ' . $group['keys'][0];
            } else {
                $nameCandidate = ucfirst(strtolower($nameCandidate));
            }

            foreach ($group['orders'] as $ord) {
                $ordNum = $ord['numeroOrdine'];
                if (!in_array($ordNum, $orderProcessed)) {
                    $clientiList[] = $ord['cliente'] . ' (Ord. #' . $ordNum . ')';
                    $orderProcessed[] = $ordNum;
                    $totaleVoucher++;
                }
                $totalePersone += $ord['quantita'];
            }

            $formattedGroups[] = [
                'nome' => $nameCandidate,
                'clienti' => $clientiList,
                'totaleVoucher' => $totaleVoucher,
                'totalePersone' => $totalePersone,
            ];
        }

        usort($formattedGroups, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));

        return $formattedGroups;
    }

    private function extractTavoloName(?string $note, ?int $currentOrderNum = null, array $visited = []): string
    {
        if (empty($note)) return '';
        
        if ($currentOrderNum !== null) {
            $visited[] = $currentOrderNum;
        }
        
        // 1. Cerca prima se c'è un riferimento a un altro numero di voucher/ordine (da 4 a 6 cifre)
        if (preg_match('/(?:voucher|ordine|ord\.?|vouch\.?|n\.?|n°|vicino\s+a|vicino|con|tavolo)\s*#?\s*(\d{4,6})\b/i', $note, $matches)) {
            $linkedNum = (int)$matches[1];
            
            if ($linkedNum !== $currentOrderNum && !in_array($linkedNum, $visited)) {
                $visited[] = $linkedNum;
                $linkedOrder = DB::table('ordini')
                    ->where('numeroOrdine', $linkedNum)
                    ->select('tavolo', 'noteOrdine', 'cognomeClienteOrdine')
                    ->first();
                
                if ($linkedOrder) {
                    if (!empty($linkedOrder->tavolo)) {
                        return trim($linkedOrder->tavolo);
                    }
                    
                    if (!empty($linkedOrder->noteOrdine)) {
                        $extractedFromLinked = $this->extractTavoloName($linkedOrder->noteOrdine, $linkedNum, $visited);
                        if (!empty($extractedFromLinked) && !$this->isBlacklistedTableWord($extractedFromLinked)) {
                            return $extractedFromLinked;
                        }
                    }
                    
                    if (!empty($linkedOrder->cognomeClienteOrdine) && !$this->isBlacklistedTableWord($linkedOrder->cognomeClienteOrdine)) {
                        return trim($linkedOrder->cognomeClienteOrdine);
                    }
                    
                    return (string)$linkedNum;
                }
            }
        }

        // 2. Cerca pattern come "tavolo [Nome]", "gruppo [Nome]", "vicino a [Nome]", "con [Nome]"
        if (preg_match('/(?:tavolo|tavoli|gruppo|vicino\s+a|vicino|per|nome|con)\s+([a-zA-Z0-9\s]{2,30})/i', $note, $matches)) {
            $val = trim($matches[1]);
            $val = preg_replace('/\s+(?:per\s+favore|favore|grazie|prego|per|vorrei|richiesta|vicino|tavolo|tavoli)\b.*/i', '', $val);
            $val = trim($val);
            
            if (!$this->isBlacklistedTableWord($val) && strlen($val) >= 2) {
                return $val;
            }
        }
        
        return '';
    }

    public function updatedTavoli($value, $key)
    {
        if ($this->risultati && isset($this->risultati['dettagli'][$key])) {
            $numeroOrdine = $this->risultati['dettagli'][$key]['numeroOrdine'];
            
            // Persist to the database
            DB::table('ordini')
                ->where('numeroOrdine', $numeroOrdine)
                ->update(['tavolo' => trim($value)]);
            
            // Keep all rows of the same order in sync
            foreach ($this->risultati['dettagli'] as &$dettaglio) {
                if ($dettaglio['numeroOrdine'] === $numeroOrdine) {
                    $idx = $dettaglio['index'];
                    $this->tavoli[$idx] = $value;
                    $dettaglio['tavolo'] = $value;
                }
            }
        }
    }
}
