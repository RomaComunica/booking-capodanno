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
                Select::make('idLocation')
                    ->label('Location')
                    ->placeholder('Tutte le Location')
                    ->options(Location::orderBy('nomeLocation')->pluck('nomeLocation', 'idLocation'))
                    ->searchable(),
                
                Select::make('periodo_speciale')
                    ->label('Periodo Festività')
                    ->placeholder('Seleziona periodo...')
                    ->options([
                        'tutto_anno' => "Tutto l'anno",
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
                    ->label('Stato Pagamento Location')
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
        
        $query = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->leftJoin('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->leftJoin('tipo_pagamenti', 'ordini.tipoPagamentoOrdine', '=', 'tipo_pagamenti.idTipoPagamento')
            ->where('ordini.statoOrdine', 2)
            ->whereDate('ordini.dataOrdine', '>=', $data['data_da'])
            ->whereDate('ordini.dataOrdine', '<=', $data['data_a']);

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
            'periodo' => date('d/m/Y', strtotime($data['data_da'])) . ' - ' . date('d/m/Y', strtotime($data['data_a'])),
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
                ->title($isPaid ? 'Ordine #' . $numeroOrdine . ': Pagamento Location rimosso (Da Pagare)' : 'Ordine #' . $numeroOrdine . ': Pagamento Location registrato (PAGATO)')
                ->success()
                ->send();
        }
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
