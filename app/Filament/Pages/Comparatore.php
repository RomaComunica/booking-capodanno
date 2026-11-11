<?php

namespace App\Filament\Pages;

use App\Services\StatisticsService;
use App\Models\Location;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Comparatore extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    
    protected static ?string $navigationLabel = 'Comparatore';
    
    protected static ?string $title = 'Comparatore Statistiche';
    
    protected static ?string $navigationGroup = 'Gestione Commerciale';
    
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.comparatore';

    const MONTHS_IT = [
        1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mag', 6 => 'Giu',
        7 => 'Lug', 8 => 'Ago', 9 => 'Set', 10 => 'Ott', 11 => 'Nov', 12 => 'Dic'
    ];

    const MONTHS_IT_FULL = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile', 5 => 'Maggio', 6 => 'Giugno',
        7 => 'Luglio', 8 => 'Agosto', 9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
    ];

    public ?array $data = [];
    public ?array $risultati = null;
    public ?array $oggiStats = null;
    public array $allYearsStats = [];

    public function mount(): void
    {
        $this->form->fill([
            'tipo_confronto' => 'questo_mese',
            'citta' => null,
            'data_riferimento' => date('Y-m-d'),
            'anno_1' => date('Y') - 1,
            'anno_2' => date('Y'),
            'periodo_1_da' => date('Y-m-d', strtotime('-30 days')),
            'periodo_1_a' => date('Y-m-d'),
            'periodo_2_da' => date('Y-m-d', strtotime('-30 days')),
            'periodo_2_a' => date('Y-m-d'),
        ]);

        $this->calcolaOggiStats();
        $this->caricaAllYearsStats();
        $this->confronta();
    }

    protected function caricaAllYearsStats()
    {
        $service = app(StatisticsService::class);
        $this->allYearsStats = $service->getAllYearsMonthlyRevenue();
    }

    protected function calcolaOggiStats()
    {
        $service = app(StatisticsService::class);
        $refDate = date('Y-m-d');
        $refTime = strtotime($refDate);
        $refYear = (int)date('Y', $refTime);
        $refMonth = (int)date('m', $refTime);
        $refDay = (int)date('d', $refTime);

        $from1 = date('Y-m-d', mktime(0,0,0, $refMonth, $refDay, $refYear - 1));
        $to1 = $from1;
        $from2 = $refDate;
        $to2 = $refDate;

        $stats1 = $service->getPeriodStats($from1, $to1);
        $stats2 = $service->getPeriodStats($from2, $to2);

        $revenueDiff = $stats2['revenue'] - $stats1['revenue'];
        $revenueDiffPct = $stats1['revenue'] > 0 ? round(($revenueDiff / $stats1['revenue']) * 100, 1) : null;

        $ticketsDiff = $stats2['tickets'] - $stats1['tickets'];
        $ticketsDiffPct = $stats1['tickets'] > 0 ? round(($ticketsDiff / $stats1['tickets']) * 100, 1) : null;

        $ordersDiff = $stats2['orders'] - $stats1['orders'];
        $ordersDiffPct = $stats1['orders'] > 0 ? round(($ordersDiff / $stats1['orders']) * 100, 1) : null;

        $this->oggiStats = [
            'periodo_1_label' => date('d/m/Y', strtotime($from1)),
            'periodo_2_label' => date('d/m/Y', strtotime($from2)),
            'revenue_1' => $stats1['revenue'],
            'revenue_2' => $stats2['revenue'],
            'revenue_diff' => $revenueDiff,
            'revenue_diff_pct' => $revenueDiffPct,
            'tickets_1' => $stats1['tickets'],
            'tickets_2' => $stats2['tickets'],
            'tickets_diff' => $ticketsDiff,
            'tickets_diff_pct' => $ticketsDiffPct,
            'orders_1' => $stats1['orders'],
            'orders_2' => $stats2['orders'],
            'orders_diff' => $ordersDiff,
            'orders_diff_pct' => $ordersDiffPct,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tipo_confronto')
                    ->label('Tipo di Confronto')
                    ->options([
                        'questo_mese' => 'Questo mese (fino a oggi) vs Anno scorso (stesso periodo)',
                        'anno_in_corso' => 'Anno in corso (fino a oggi) vs Anno scorso (stesso periodo)',
                        'anno_contro_anno' => 'Anno intero contro Anno intero',
                        'halloween' => 'Halloween (1 Ott - 1 Nov) vs Anno scorso',
                        'san_valentino' => 'San Valentino (14 Gen - 14 Feb) vs Anno scorso',
                        'festa_donna' => 'Festa della Donna (8 Feb - 8 Mar) vs Anno scorso',
                        'periodo_personalizzato' => 'Periodi personalizzati (range di date)',
                    ])
                    ->required()
                    ->live()
                    ->columnSpan(['default' => 4, 'md' => 3]),

                Select::make('citta')
                    ->label('Città')
                    ->placeholder('Tutte le Città')
                    ->options(fn () => Location::getAvailableCities())
                    ->columnSpan(['default' => 4, 'md' => 1]),

                // Data di riferimento per oggi, mese, anno in corso, festività
                DatePicker::make('data_riferimento')
                    ->label('Data / Anno Riferimento')
                    ->default(now())
                    ->required()
                    ->visible(fn ($get) => in_array($get('tipo_confronto'), ['oggi', 'questo_mese', 'anno_in_corso', 'halloween', 'san_valentino', 'festa_donna']))
                    ->columnSpan(4),

                // Anni per Anno contro Anno
                Select::make('anno_1')
                    ->label('Anno 1 (Precedente)')
                    ->options(function () {
                        $years = [];
                        for ($y = date('Y'); $y >= 2017; $y--) {
                            $years[(string)$y] = $y;
                        }
                        return $years;
                    })
                    ->required()
                    ->visible(fn ($get) => $get('tipo_confronto') === 'anno_contro_anno')
                    ->columnSpan(['default' => 4, 'sm' => 2]),

                Select::make('anno_2')
                    ->label('Anno 2 (Successivo)')
                    ->options(function () {
                        $years = [];
                        for ($y = date('Y') + 1; $y >= 2017; $y--) {
                            $years[(string)$y] = $y;
                        }
                        return $years;
                    })
                    ->required()
                    ->visible(fn ($get) => $get('tipo_confronto') === 'anno_contro_anno')
                    ->columnSpan(['default' => 4, 'sm' => 2]),

                // Date per Periodo Personalizzato
                DatePicker::make('periodo_1_da')
                    ->label('Periodo 1: Da')
                    ->required()
                    ->visible(fn ($get) => $get('tipo_confronto') === 'periodo_personalizzato')
                    ->columnSpan(['default' => 2, 'sm' => 1]),

                DatePicker::make('periodo_1_a')
                    ->label('A')
                    ->required()
                    ->visible(fn ($get) => $get('tipo_confronto') === 'periodo_personalizzato')
                    ->columnSpan(['default' => 2, 'sm' => 1]),

                DatePicker::make('periodo_2_da')
                    ->label('Periodo 2: Da')
                    ->required()
                    ->visible(fn ($get) => $get('tipo_confronto') === 'periodo_personalizzato')
                    ->columnSpan(['default' => 2, 'sm' => 1]),

                DatePicker::make('periodo_2_a')
                    ->label('A')
                    ->required()
                    ->visible(fn ($get) => $get('tipo_confronto') === 'periodo_personalizzato')
                    ->columnSpan(['default' => 2, 'sm' => 1]),
            ])
            ->columns(4)
            ->statePath('data');
    }

    public function confronta()
    {
        $data = $this->form->getState();
        $tipo = $data['tipo_confronto'];
        $service = app(StatisticsService::class);

        $from1 = $to1 = $from2 = $to2 = '';
        $chartLabels = [];
        $chartData1 = [];
        $chartData2 = [];
        $isMonthlyChart = false;

        $refDate = $data['data_riferimento'] ?? date('Y-m-d');
        $refTime = strtotime($refDate);
        $refYear = (int)date('Y', $refTime);
        $refMonth = (int)date('m', $refTime);
        $refDay = (int)date('d', $refTime);

        $short1 = $short2 = '';
        $isPrimaryComparison = in_array($tipo, ['oggi', 'questo_mese', 'anno_in_corso', 'anno_contro_anno', 'halloween', 'san_valentino', 'festa_donna']);

        if ($tipo === 'oggi') {
            $from1 = date('Y-m-d', mktime(0,0,0, $refMonth, $refDay, $refYear - 1));
            $to1 = $from1;
            $from2 = $refDate;
            $to2 = $refDate;
            
            $label1 = "Oggi L.Y. (" . date('d/m/Y', strtotime($from1)) . ")";
            $label2 = "Oggi (" . date('d/m/Y', strtotime($from2)) . ")";
            $short1 = date('d/m/y', strtotime($from1));
            $short2 = date('d/m/y', strtotime($from2));
        } elseif ($tipo === 'questo_mese') {
            $from1 = date('Y-m-d', mktime(0,0,0, $refMonth, 1, $refYear - 1));
            $to1 = date('Y-m-d', mktime(0,0,0, $refMonth, $refDay, $refYear - 1));
            $from2 = date('Y-m-d', mktime(0,0,0, $refMonth, 1, $refYear));
            $to2 = $refDate;
            
            $m1 = (int)date('m', strtotime($from1));
            $y1 = date('Y', strtotime($from1));
            $m2 = (int)date('m', strtotime($from2));
            $y2 = date('Y', strtotime($from2));

            $label1 = self::MONTHS_IT_FULL[$m1] . " " . $y1 . " (fino a " . $refDay . ")";
            $label2 = self::MONTHS_IT_FULL[$m2] . " " . $y2 . " (fino a " . $refDay . ")";
            $short1 = self::MONTHS_IT[$m1] . " " . date('y', strtotime($from1));
            $short2 = self::MONTHS_IT[$m2] . " " . date('y', strtotime($from2));
        } elseif ($tipo === 'anno_in_corso') {
            $from1 = ($refYear - 1) . '-01-01';
            $to1 = date('Y-m-d', mktime(0,0,0, $refMonth, $refDay, $refYear - 1));
            $from2 = $refYear . '-01-01';
            $to2 = $refDate;
            
            $label1 = "Anno scorso (fino al " . date('d/m', strtotime($to1)) . ")";
            $label2 = "Anno in corso (fino al " . date('d/m', strtotime($to2)) . ")";
            $short1 = (string)($refYear - 1);
            $short2 = (string)$refYear;
        } elseif ($tipo === 'anno_contro_anno') {
            $year1 = $data['anno_1'];
            $year2 = $data['anno_2'];
            $from1 = $year1 . '-01-01';
            $to1 = $year1 . '-12-31';
            $from2 = $year2 . '-01-01';
            $to2 = $year2 . '-12-31';
            
            $label1 = "Anno " . $year1;
            $label2 = "Anno " . $year2;
            $short1 = (string)$year1;
            $short2 = (string)$year2;
            $isMonthlyChart = false;
        } elseif ($tipo === 'halloween') {
            $from1 = ($refYear - 1) . '-10-01';
            $to1 = ($refYear - 1) . '-11-01';
            $from2 = $refYear . '-10-01';
            $to2 = $refYear . '-11-01';
            
            $label1 = "Halloween " . ($refYear - 1) . " (1 Ott - 1 Nov)";
            $label2 = "Halloween " . $refYear . " (1 Ott - 1 Nov)";
            $short1 = "Hall. " . date('y', strtotime($from1));
            $short2 = "Hall. " . date('y', strtotime($from2));
        } elseif ($tipo === 'san_valentino') {
            $from1 = ($refYear - 1) . '-01-14';
            $to1 = ($refYear - 1) . '-02-14';
            $from2 = $refYear . '-01-14';
            $to2 = $refYear . '-02-14';
            
            $label1 = "San Valentino " . ($refYear - 1) . " (14 Gen - 14 Feb)";
            $label2 = "San Valentino " . $refYear . " (14 Gen - 14 Feb)";
            $short1 = "S.Val. " . date('y', strtotime($from1));
            $short2 = "S.Val. " . date('y', strtotime($from2));
        } elseif ($tipo === 'festa_donna') {
            $from1 = ($refYear - 1) . '-02-08';
            $to1 = ($refYear - 1) . '-03-08';
            $from2 = $refYear . '-02-08';
            $to2 = $refYear . '-03-08';
            
            $label1 = "Festa della Donna " . ($refYear - 1) . " (8 Feb - 8 Mar)";
            $label2 = "Festa della Donna " . $refYear . " (8 Feb - 8 Mar)";
            $short1 = "Donna " . date('y', strtotime($from1));
            $short2 = "Donna " . date('y', strtotime($from2));
        } else { // periodo_personalizzato
            $from1 = $data['periodo_1_da'];
            $to1 = $data['periodo_1_a'];
            $from2 = $data['periodo_2_da'];
            $to2 = $data['periodo_2_a'];
            
            $label1 = "Periodo 1 (" . date('d/m/Y', strtotime($from1)) . " - " . date('d/m/Y', strtotime($to1)) . ")";
            $label2 = "Periodo 2 (" . date('d/m/Y', strtotime($from2)) . " - " . date('d/m/Y', strtotime($to2)) . ")";
            $short1 = date('d/m/y', strtotime($from1)) . "-" . date('d/m/y', strtotime($to1));
            $short2 = date('d/m/y', strtotime($from2)) . "-" . date('d/m/y', strtotime($to2));
        }

        // Fetch aggregate stats
        $cityFilter = !empty($data['citta']) ? (string) $data['citta'] : null;
        $stats1 = $service->getPeriodStats($from1, $to1, $cityFilter);
        $stats2 = $service->getPeriodStats($from2, $to2, $cityFilter);

        // Fetch location breakdowns
        $locStats1 = $service->getPeriodLocationStats($from1, $to1, $cityFilter);
        $locStats2 = $service->getPeriodLocationStats($from2, $to2, $cityFilter);

        $allLocationsQuery = Location::query();
        if ($cityFilter) {
            if (strtolower($cityFilter) === 'roma') {
                $allLocationsQuery->where(function ($q) {
                    $q->where('cittaLocation', 'Roma')
                      ->orWhereNull('cittaLocation')
                      ->orWhere('cittaLocation', '');
                });
            } else {
                $allLocationsQuery->where('cittaLocation', $cityFilter);
            }
        }
        $allLocations = $allLocationsQuery->get();
        $locationBreakdown = [];
        $cityGroups = [];

        foreach ($allLocations as $loc) {
            $l1 = $locStats1->get($loc->idLocation);
            $l2 = $locStats2->get($loc->idLocation);

            $t1 = $l1 ? (int)$l1->total_tickets : 0;
            $t2 = $l2 ? (int)$l2->total_tickets : 0;
            $r1 = $l1 ? (float)$l1->total_revenue : 0.0;
            $r2 = $l2 ? (float)$l2->total_revenue : 0.0;
            $cittaLoc = ucwords(strtolower(trim((string) ($loc->cittaLocation ?: 'Roma')))) ?: 'Roma';

            if ($t1 > 0 || $t2 > 0 || $r1 > 0 || $r2 > 0) {
                $locationBreakdown[] = [
                    'nomeLocation' => $loc->nomeLocation,
                    'cittaLocation' => $cittaLoc,
                    'tickets_1' => $t1,
                    'tickets_2' => $t2,
                    'tickets_diff' => $t2 - $t1,
                    'tickets_diff_pct' => $t1 > 0 ? round((($t2 - $t1) / $t1) * 100, 1) : null,
                    'revenue_1' => $r1,
                    'revenue_2' => $r2,
                    'revenue_diff' => $r2 - $r1,
                    'revenue_diff_pct' => $r1 > 0 ? round((($r2 - $r1) / $r1) * 100, 1) : null,
                ];

                if (!isset($cityGroups[$cittaLoc])) {
                    $cityGroups[$cittaLoc] = [
                        'citta' => $cittaLoc,
                        'tickets_1' => 0,
                        'tickets_2' => 0,
                        'revenue_1' => 0.0,
                        'revenue_2' => 0.0,
                    ];
                }
                $cityGroups[$cittaLoc]['tickets_1'] += $t1;
                $cityGroups[$cittaLoc]['tickets_2'] += $t2;
                $cityGroups[$cittaLoc]['revenue_1'] += $r1;
                $cityGroups[$cittaLoc]['revenue_2'] += $r2;
            }
        }

        $cityBreakdown = [];
        foreach ($cityGroups as $cg) {
            $tDiff = $cg['tickets_2'] - $cg['tickets_1'];
            $rDiff = $cg['revenue_2'] - $cg['revenue_1'];
            $cityBreakdown[] = [
                'citta' => $cg['citta'],
                'tickets_1' => $cg['tickets_1'],
                'tickets_2' => $cg['tickets_2'],
                'tickets_diff' => $tDiff,
                'tickets_diff_pct' => $cg['tickets_1'] > 0 ? round(($tDiff / $cg['tickets_1']) * 100, 1) : null,
                'revenue_1' => $cg['revenue_1'],
                'revenue_2' => $cg['revenue_2'],
                'revenue_diff' => $rDiff,
                'revenue_diff_pct' => $cg['revenue_1'] > 0 ? round(($rDiff / $cg['revenue_1']) * 100, 1) : null,
            ];
        }
        usort($cityBreakdown, fn($a, $b) => $b['revenue_2'] <=> $a['revenue_2']);

        // Sort locations by highest revenue in period 2
        usort($locationBreakdown, fn($a, $b) => $b['revenue_2'] <=> $a['revenue_2']);

        // Prepare chart data
        if ($isMonthlyChart) {
            $monthlyDetails = $service->compareYearsDetailed($year1, $year2, $cityFilter);
            foreach ($monthlyDetails as $m) {
                $chartLabels[] = $m['month'];
                $chartData1[] = [
                    'revenue' => $m['revenue_year1'],
                    'tickets' => $m['tickets_year1']
                ];
                $chartData2[] = [
                    'revenue' => $m['revenue_year2'],
                    'tickets' => $m['tickets_year2']
                ];
            }
        } else {
            $daily1 = $service->getPeriodDailyStats($from1, $to1, $cityFilter);
            $daily2 = $service->getPeriodDailyStats($from2, $to2, $cityFilter);

            $count = max(count($daily1), count($daily2));
            $keys1 = array_keys($daily1);
            $keys2 = array_keys($daily2);

            for ($i = 0; $i < $count; $i++) {
                // If it is just a single day comparison, label with exact date
                if ($tipo === 'oggi') {
                    $chartLabels[] = "Oggi";
                } else {
                    $chartLabels[] = "G. " . ($i + 1);
                }
                
                $d1 = isset($keys1[$i]) ? $daily1[$keys1[$i]] : ['revenue' => 0.0, 'tickets' => 0];
                $d2 = isset($keys2[$i]) ? $daily2[$keys2[$i]] : ['revenue' => 0.0, 'tickets' => 0];

                $d1['date'] = isset($keys1[$i]) ? $keys1[$i] : null;
                $d2['date'] = isset($keys2[$i]) ? $keys2[$i] : null;

                $chartData1[] = $d1;
                $chartData2[] = $d2;
            }
        }

        // Calculate differences
        $revenueDiff = $stats2['revenue'] - $stats1['revenue'];
        $revenueDiffPct = $stats1['revenue'] > 0 ? round(($revenueDiff / $stats1['revenue']) * 100, 1) : null;

        $ticketsDiff = $stats2['tickets'] - $stats1['tickets'];
        $ticketsDiffPct = $stats1['tickets'] > 0 ? round(($ticketsDiff / $stats1['tickets']) * 100, 1) : null;

        $ordersDiff = $stats2['orders'] - $stats1['orders'];
        $ordersDiffPct = $stats1['orders'] > 0 ? round(($ordersDiff / $stats1['orders']) * 100, 1) : null;

        $this->risultati = [
            'periodo_1_label' => $label1,
            'periodo_2_label' => $label2,
            'periodo_1_short' => $short1,
            'periodo_2_short' => $short2,
            'isPrimaryComparison' => $isPrimaryComparison,
            'revenue_1' => $stats1['revenue'],
            'revenue_2' => $stats2['revenue'],
            'revenue_diff' => $revenueDiff,
            'revenue_diff_pct' => $revenueDiffPct,
            'tickets_1' => $stats1['tickets'],
            'tickets_2' => $stats2['tickets'],
            'tickets_diff' => $ticketsDiff,
            'tickets_diff_pct' => $ticketsDiffPct,
            'orders_1' => $stats1['orders'],
            'orders_2' => $stats2['orders'],
            'orders_diff' => $ordersDiff,
            'orders_diff_pct' => $ordersDiffPct,
            'citta_filtro' => $cityFilter,
            'cityBreakdown' => $cityBreakdown,
            'locationBreakdown' => $locationBreakdown,
            'chartLabels' => $chartLabels,
            'chartData1' => $chartData1,
            'chartData2' => $chartData2,
            'isMonthly' => $isMonthlyChart
        ];

        // Dispatch browser event to draw/update charts
        $this->dispatch('draw-comparison-charts', resultados: $this->risultati);
    }
}
