<?php

namespace App\Services;

use App\Models\Ordine;
use App\Models\RigaOrdine;
use App\Models\Location;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    const MONTHS_IT = [
        1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mag', 6 => 'Giu',
        7 => 'Lug', 8 => 'Ago', 9 => 'Set', 10 => 'Ott', 11 => 'Nov', 12 => 'Dic'
    ];
    public static function getDefaultDashboardPeriod(): string
    {
        return ((int) date('n') >= 11) ? 'capodanno' : 'tutto_anno';
    }

    public static function resolvePeriodRange($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null): array
    {
        $year = (int) ($year ?: \App\Models\Setting::getVal('anno_riferimento', date('Y')));
        $period = $period ?: 'tutto_anno';

        switch ($period) {
            case 'capodanno':
                $fromDate = "{$year}-11-01";
                $toDate = "{$year}-12-31";
                $label = "Capodanno {$year} (1 Nov - 31 Dic)";
                $shortLabel = "Capodanno {$year}";
                break;
            case 'halloween':
                $fromDate = "{$year}-10-01";
                $toDate = "{$year}-11-01";
                $label = "Halloween {$year} (1 Ott - 1 Nov)";
                $shortLabel = "Halloween {$year}";
                break;
            case 'san_valentino':
                $fromDate = "{$year}-01-14";
                $toDate = "{$year}-02-14";
                $label = "San Valentino {$year} (14 Gen - 14 Feb)";
                $shortLabel = "San Valentino {$year}";
                break;
            case 'festa_donna':
                $fromDate = "{$year}-02-08";
                $toDate = "{$year}-03-08";
                $label = "Festa della Donna {$year} (8 Feb - 8 Mar)";
                $shortLabel = "Festa della Donna {$year}";
                break;
            case 'personalizzato':
                $fromDate = !empty($customFrom) ? date('Y-m-d', strtotime($customFrom)) : "{$year}-01-01";
                $toDate = !empty($customTo) ? date('Y-m-d', strtotime($customTo)) : "{$year}-12-31";
                if ($fromDate > $toDate) {
                    [$fromDate, $toDate] = [$toDate, $fromDate];
                }
                $fmtFrom = date('d/m/Y', strtotime($fromDate));
                $fmtTo = date('d/m/Y', strtotime($toDate));
                $label = "Dal {$fmtFrom} al {$fmtTo}";
                $shortLabel = "{$fmtFrom} - {$fmtTo}";
                break;
            case 'tutto_anno':
            default:
                $fromDate = "{$year}-01-01";
                $toDate = "{$year}-12-31";
                $label = "Tutto l'anno {$year}";
                $shortLabel = (string) $year;
                break;
        }

        return [
            'year' => $year,
            'period' => $period,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'from' => $fromDate . ' 00:00:00',
            'to' => $toDate . ' 23:59:59',
            'label' => $label,
            'short_label' => $shortLabel,
        ];
    }

    public function getDashboardStats($year = null, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        $year = $year ?? \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);

        $completedOrders = Ordine::whereBetween('dataOrdine', [$range['from'], $range['to']])->where('statoOrdine', 2)->count();
        $pendingOrders = Ordine::whereBetween('dataOrdine', [$range['from'], $range['to']])->where('statoOrdine', 0)->count();
        $cancelledOrders = Ordine::whereBetween('dataOrdine', [$range['from'], $range['to']])->where('statoOrdine', 1)->count();
        $totalOrders = $completedOrders + $pendingOrders + $cancelledOrders;

        // Biglietti venduti raggruppati per metodo di pagamento con etichette uniformate e totale in €
        $payments = DB::table('tipo_pagamenti')->pluck('nomePagamento', 'idTipoPagamento')->toArray();

        $rows = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
            ->whereNotNull('ordini.dataOrdine')
            ->where('ordini.statoOrdine', 2)
            ->select([
                'ordini.tipoPagamentoOrdine',
                DB::raw('SUM(righe_ordine.quantitaIngressoOrdine) as totale_biglietti'),
                DB::raw('SUM(righe_ordine.quantitaIngressoOrdine * righe_ordine.prezzoIngressoOrdine) as totale_euro')
            ])
            ->groupBy('ordini.tipoPagamentoOrdine')
            ->get();

        $ticketsByPayment = [];
        foreach ($rows as $row) {
            $id = (int)$row->tipoPagamentoOrdine;
            
            // Risoluzione iniziale nome metodo
            if ($id === 0 || empty($id)) {
                $rawName = 'Bonifico Bancario';
            } elseif (isset($payments[$id])) {
                $rawName = $payments[$id];
            } else {
                $rawName = 'Bonifico Bancario';
            }

            $name = $rawName;

            if (!isset($ticketsByPayment[$name])) {
                $ticketsByPayment[$name] = [
                    'tickets' => 0,
                    'revenue' => 0.0,
                ];
            }

            $ticketsByPayment[$name]['tickets'] += (int)$row->totale_biglietti;
            $ticketsByPayment[$name]['revenue'] += (float)$row->totale_euro;
        }

        uasort($ticketsByPayment, function ($a, $b) {
            return $b['tickets'] <=> $a['tickets'];
        });

        $totalRevenue = $this->getTotalRevenue($year, $period, $customFrom, $customTo);
        $totalTickets = $this->getTotalTickets($year, $period, $customFrom, $customTo);
        $netProfitTickets = $this->getTotalCommissions($year, $period, $customFrom, $customTo);
        $locationFinancials = $this->getLocationFinancials($year, $period, $customFrom, $customTo);
        $partnerFinancials = $this->getPartnerFinancials($year, $period, $customFrom, $customTo);
        $partnerCommissions = $partnerFinancials['totale_spettante'];
        $partnerOrdersCount = $this->getPartnerOrdersCount($year, $period, $customFrom, $customTo);

        $extraIncomes = (float)\App\Models\EntrataUscita::whereDate('dataOperazione', '>=', $range['from_date'])->whereDate('dataOperazione', '<=', $range['to_date'])
            ->where('tipoOperazione', 'E')
            ->sum('importo');

        $extraExpenses = (float)\App\Models\EntrataUscita::whereDate('dataOperazione', '>=', $range['from_date'])->whereDate('dataOperazione', '<=', $range['to_date'])
            ->where('tipoOperazione', 'U')
            ->sum('importo');

        $profitAfterExpenses = round($netProfitTickets - $partnerCommissions + $extraIncomes - $extraExpenses, 2);

        return [
            'period_label' => $range['label'],
            'period_short_label' => $range['short_label'],
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'pending_orders' => $pendingOrders,
            'cancelled_orders' => $cancelledOrders,
            'total_tickets' => $totalTickets,
            'tickets_by_payment' => $ticketsByPayment,
            'total_commissions' => $netProfitTickets,
            'total_margin' => round($totalRevenue - $netProfitTickets, 2),
            'net_profit_tickets' => $netProfitTickets,
            'partner_commissions' => $partnerCommissions,
            'location_financials' => $locationFinancials,
            'partner_financials' => $partnerFinancials,
            'partner_orders_count' => $partnerOrdersCount,
            'extra_incomes' => $extraIncomes,
            'extra_expenses' => $extraExpenses,
            'profit_after_expenses' => $profitAfterExpenses,
            'total_profit' => $profitAfterExpenses,
            'locations_count' => Location::count(),
            'active_locations' => Location::where('attivaLocation', true)->count(),
            'partners_count' => Partner::count(),
        ];
    }

    /**
     * Get total revenue for year
     */
    public function getTotalRevenue($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);
        return Ordine::whereBetween('dataOrdine', [$range['from'], $range['to']])
            ->where('statoOrdine', 2)
            ->sum('totaleOrdine');
    }

    private function _unusedLegacyRevenue($year)
    {
        return Ordine::whereYear('dataOrdine', $year)
            ->where('statoOrdine', 2)
            ->sum('totaleOrdine');
    }

    /**
     * Get total orders for year
     */
    public function getTotalOrders($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);
        return Ordine::whereBetween('dataOrdine', [$range['from'], $range['to']])->count();
    }

    private function _unusedLegacyOrders($year)
    {
        return Ordine::whereYear('dataOrdine', $year)->count();
    }

    /**
     * Get total tickets sold for year
     */
    public function getTotalTickets($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);
        return RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
            ->where('ordini.statoOrdine', 2)
            ->sum('righe_ordine.quantitaIngressoOrdine');
    }

    private function _unusedLegacyTickets($year)
    {
        return RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereNotNull('ordini.dataOrdine')
            ->where('ordini.statoOrdine', 2)
            ->sum('righe_ordine.quantitaIngressoOrdine');
    }

    /**
     * Calculate total commissions to partners/locations
     */
    public function getTotalCommissions($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);
        $righe = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
            ->whereNotNull('ordini.dataOrdine')
            ->where('ordini.statoOrdine', 2)
            ->select([
                'righe_ordine.prezzoIngressoOrdine',
                'righe_ordine.quantitaIngressoOrdine',
                'righe_ordine.idIngressoOrdine',
                'righe_ordine.nomeIngressoOrdine',
                'ordini.idLocationOrdine',
            ])
            ->get();

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

        $totalCommissions = 0;
        foreach ($righe as $riga) {
            $subtotal = (float)$riga->prezzoIngressoOrdine * (int)$riga->quantitaIngressoOrdine;
            
            $tipologia = $tipologieByCont[$riga->idIngressoOrdine]
                ?? $tipologieById[$riga->idIngressoOrdine]
                ?? ($tipologieByNameLoc[trim($riga->nomeIngressoOrdine) . '_' . $riga->idLocationOrdine] ?? null)
                ?? ($tipologieByName[trim($riga->nomeIngressoOrdine)] ?? null);

            $tipoProvvigione = $tipologia ? (int)$tipologia->tipoProvvigione : 0;
            $valoreProvvigione = $tipologia ? (float)$tipologia->valoreProvvigione : 0.0;

            if ($tipoProvvigione == 1) {
                // Percentuale
                $commission = ($subtotal / 100) * $valoreProvvigione;
            } else {
                // Fisso per biglietto
                $commission = $valoreProvvigione * (int)$riga->quantitaIngressoOrdine;
            }
            
            $totalCommissions += $commission;
        }

        return round($totalCommissions, 2);
    }

    /**
     * Get location financials (spettante, gia pagato, da pagare residuo)
     */
    public function getLocationFinancials($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null): array
    {
        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);
        $righe = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
            ->whereNotNull('ordini.dataOrdine')
            ->where('ordini.statoOrdine', 2)
            ->select([
                DB::raw('COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0) as prezzoIngressoOrdine'),
                'righe_ordine.quantitaIngressoOrdine',
                'righe_ordine.idIngressoOrdine',
                'righe_ordine.nomeIngressoOrdine',
                'ordini.idLocationOrdine',
                'ordini.dataPagamentoOrdineFornitore',
            ])
            ->get();

        if ($righe->isEmpty()) {
            return [
                'totale_spettante' => 0.0,
                'gia_pagato' => 0.0,
                'da_pagare_residuo' => 0.0,
            ];
        }

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

        $totaleDare = 0.0;
        $totaleGiaPagato = 0.0;

        foreach ($righe as $riga) {
            $venduto = (float)$riga->prezzoIngressoOrdine * (int)$riga->quantitaIngressoOrdine;

            $tipologia = $tipologieByCont[$riga->idIngressoOrdine]
                ?? $tipologieById[$riga->idIngressoOrdine]
                ?? ($tipologieByNameLoc[trim($riga->nomeIngressoOrdine) . '_' . $riga->idLocationOrdine] ?? null)
                ?? ($tipologieByName[trim($riga->nomeIngressoOrdine)] ?? null);

            $tipoProvvigione = $tipologia ? (int)$tipologia->tipoProvvigione : 0;
            $valoreProvvigione = $tipologia ? (float)$tipologia->valoreProvvigione : 0.0;

            if ($tipoProvvigione == 1) {
                $commission = ($venduto / 100) * $valoreProvvigione;
            } else {
                $commission = $valoreProvvigione * (int)$riga->quantitaIngressoOrdine;
            }

            $dare = max(0, $venduto - $commission);
            $totaleDare += $dare;

            $isPaid = !empty($riga->dataPagamentoOrdineFornitore)
                && $riga->dataPagamentoOrdineFornitore !== '0000-00-00 00:00:00'
                && $riga->dataPagamentoOrdineFornitore !== '';

            if ($isPaid) {
                $totaleGiaPagato += $dare;
            }
        }

        return [
            'totale_spettante' => round($totaleDare, 2),
            'gia_pagato' => round($totaleGiaPagato, 2),
            'da_pagare_residuo' => round(max(0, $totaleDare - $totaleGiaPagato), 2),
        ];
    }

    /**
     * Get partner financials (spettante, gia pagato, da pagare residuo)
     */
    public function getPartnerFinancials($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null): array
    {
        $idPartnerGestore = (int)\App\Models\Setting::getVal('id_partner_gestore', 5);
        $partnerConProvvigione = Partner::idsConProvvigione();

        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);
        $righe = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
            ->whereNotNull('ordini.dataOrdine')
            ->where('ordini.statoOrdine', 2)
            ->whereNotNull('ordini.idPartnerOrdine')
            ->where('ordini.idPartnerOrdine', '>', 0)
            ->where('ordini.idPartnerOrdine', '!=', $idPartnerGestore)
            ->whereIn('ordini.idPartnerOrdine', $partnerConProvvigione)
            ->select([
                DB::raw('COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0) as prezzoIngressoOrdine'),
                'righe_ordine.quantitaIngressoOrdine',
                'righe_ordine.idIngressoOrdine',
                'righe_ordine.nomeIngressoOrdine',
                'ordini.idLocationOrdine',
                'ordini.idPartnerOrdine',
                'ordini.dataPagamentoOrdinePartner',
            ])
            ->get();

        if ($righe->isEmpty()) {
            return [
                'totale_spettante' => 0.0,
                'gia_pagato' => 0.0,
                'da_pagare_residuo' => 0.0,
            ];
        }

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

        $totalPartnerCommissions = 0.0;
        $totaleGiaPagatoPartner = 0.0;
        $tipiPartner = Partner::whereIn('idPartner', $righe->pluck('idPartnerOrdine')->unique()->filter())
            ->pluck('tipoPartner', 'idPartner');

        foreach ($righe as $riga) {
            $subtotal = (float)$riga->prezzoIngressoOrdine * (int)$riga->quantitaIngressoOrdine;

            $tipologia = $tipologieByCont[$riga->idIngressoOrdine]
                ?? $tipologieById[$riga->idIngressoOrdine]
                ?? ($tipologieByNameLoc[trim($riga->nomeIngressoOrdine) . '_' . $riga->idLocationOrdine] ?? null)
                ?? ($tipologieByName[trim($riga->nomeIngressoOrdine)] ?? null);

            $tipoProv = $tipologia ? (int)$tipologia->tipoProvvigionePartner : 0;
            $valoreProv = $tipologia ? (float)$tipologia->valoreProvvigionePartner : 0.0;

            $commission = Partner::calcolaImportoProvvigione(
                $tipiPartner[$riga->idPartnerOrdine] ?? Partner::TIPO_DIRETTO,
                $subtotal,
                (int) $riga->quantitaIngressoOrdine,
                $tipoProv,
                $valoreProv
            );

            $totalPartnerCommissions += $commission;

            $isPaid = !empty($riga->dataPagamentoOrdinePartner)
                && $riga->dataPagamentoOrdinePartner !== '0000-00-00 00:00:00'
                && $riga->dataPagamentoOrdinePartner !== '';

            if ($isPaid) {
                $totaleGiaPagatoPartner += $commission;
            }
        }

        return [
            'totale_spettante' => round($totalPartnerCommissions, 2),
            'gia_pagato' => round($totaleGiaPagatoPartner, 2),
            'da_pagare_residuo' => round(max(0, $totalPartnerCommissions - $totaleGiaPagatoPartner), 2),
        ];
    }

    /**
     * Calculate total partner commissions for year
     */
    public function getTotalPartnerCommissions($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        return $this->getPartnerFinancials($year, $period, $customFrom, $customTo)['totale_spettante'];
    }

    /**
     * Get count of completed orders associated with external partners for year
     */
    public function getPartnerOrdersCount($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);
        $idPartnerGestore = (int)\App\Models\Setting::getVal('id_partner_gestore', 5);

        return Ordine::whereBetween('dataOrdine', [$range['from'], $range['to']])
            ->where('statoOrdine', 2)
            ->whereNotNull('idPartnerOrdine')
            ->where('idPartnerOrdine', '>', 0)
            ->where('idPartnerOrdine', '!=', $idPartnerGestore)
            ->whereIn('idPartnerOrdine', Partner::idsConProvvigione())
            ->count();
    }

    private function _unusedLegacyPartnerCount($year)
    {
        $idPartnerGestore = (int)\App\Models\Setting::getVal('id_partner_gestore', 5);

        return Ordine::whereYear('dataOrdine', $year)
            ->where('statoOrdine', 2)
            ->whereNotNull('idPartnerOrdine')
            ->where('idPartnerOrdine', '>', 0)
            ->where('idPartnerOrdine', '!=', $idPartnerGestore)
            ->count();
    }

    /**
     * Calculate total margin (revenue - commissions) without external ledger
     */
    public function getTotalMargin($year)
    {
        return round($this->getTotalRevenue($year) - $this->getTotalCommissions($year), 2);
    }

    /**
     * Calculate total net profit (net commissions - partner commissions + entrate - uscite)
     */
    public function getTotalProfit($year, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        $netProfitTickets = $this->getTotalCommissions($year, $period, $customFrom, $customTo);
        $locationFinancials = $this->getLocationFinancials($year, $period, $customFrom, $customTo);
        $partnerFinancials = $this->getPartnerFinancials($year, $period, $customFrom, $customTo);
        $partnerCommissions = $partnerFinancials['totale_spettante'];

        // Add Entrate and subtract Uscite from the new ledger table
        $entrate = \App\Models\EntrataUscita::whereYear('dataOperazione', $year)
            ->where('tipoOperazione', 'E')
            ->sum('importo');

        $uscite = \App\Models\EntrataUscita::whereYear('dataOperazione', $year)
            ->where('tipoOperazione', 'U')
            ->sum('importo');

        return round($netProfitTickets - $partnerCommissions + $entrate - $uscite, 2);
    }

    /**
     * Get monthly revenue comparison for all available years in the database
     */
    public function getAllYearsMonthlyRevenue(): array
    {
        $palette = [
            '#0c5289', // Primary Brand Blue
            '#29f48f', // Vibrant Neon Mint Green
            '#DD076D', // Electric Fuchsia / Magenta
            '#f59e0b', // Vibrant Amber / Gold
            '#8b5cf6', // Violet
            '#06b6d4', // Cyan
            '#ec4899', // Pink
            '#10b981', // Emerald
            '#6366f1', // Indigo
            '#f97316', // Orange
            '#64748b', // Slate
            '#14b8a6', // Teal
        ];

        $orders = Ordine::where('statoOrdine', 2)
            ->whereNotNull('dataOrdine')
            ->select(['dataOrdine', 'totaleOrdine'])
            ->get();

        $yearsSet = [];
        $matrix = [];
        $dailyMatrix = [];
        $yearlyTotals = [];

        foreach ($orders as $order) {
            $time = strtotime($order->dataOrdine);
            if (!$time) continue;
            $y = (int)date('Y', $time);
            $m = (int)date('n', $time);
            $d = (int)date('j', $time);
            
            $yearsSet[$y] = true;
            if (!isset($matrix[$y])) {
                $matrix[$y] = array_fill(1, 12, 0.0);
                $dailyMatrix[$y] = [];
                for ($mon = 1; $mon <= 12; $mon++) {
                    $dailyMatrix[$y][$mon] = array_fill(1, 31, 0.0);
                }
                $yearlyTotals[$y] = 0.0;
            }
            $matrix[$y][$m] += (float)$order->totaleOrdine;
            $dailyMatrix[$y][$m][$d] += (float)$order->totaleOrdine;
            $yearlyTotals[$y] += (float)$order->totaleOrdine;
        }

        $currentYear = (int)date('Y');
        $yearsSet[$currentYear] = true;
        if (!isset($matrix[$currentYear])) {
            $matrix[$currentYear] = array_fill(1, 12, 0.0);
            $dailyMatrix[$currentYear] = [];
            for ($mon = 1; $mon <= 12; $mon++) {
                $dailyMatrix[$currentYear][$mon] = array_fill(1, 31, 0.0);
            }
            $yearlyTotals[$currentYear] = 0.0;
        }

        $years = array_keys($yearsSet);
        sort($years);

        $monthsLabels = array_values(self::MONTHS_IT);

        $datasets = [];
        $colorIndex = 0;
        $prevTotal = null;

        foreach ($years as $y) {
            $monthlyData = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthlyData[] = round($matrix[$y][$m] ?? 0.0, 2);
            }

            $formattedDaily = [];
            for ($m = 1; $m <= 12; $m++) {
                $mKey = str_pad((string)$m, 2, '0', STR_PAD_LEFT);
                $formattedDaily[$mKey] = [];
                for ($d = 1; $d <= 31; $d++) {
                    $formattedDaily[$mKey][] = round($dailyMatrix[$y][$m][$d] ?? 0.0, 2);
                }
            }

            $color = $palette[$colorIndex % count($palette)];
            $colorIndex++;

            $currentTotal = round($yearlyTotals[$y] ?? 0.0, 2);
            $growthPct = null;
            if ($prevTotal !== null && $prevTotal > 0) {
                $growthPct = round((($currentTotal - $prevTotal) / $prevTotal) * 100, 1);
            }
            $prevTotal = $currentTotal;

            $datasets[] = [
                'year' => (string)$y,
                'label' => 'Anno ' . $y,
                'data' => $monthlyData,
                'daily' => $formattedDaily,
                'monthly_totals' => $matrix[$y],
                'total' => $currentTotal,
                'growth_pct' => $growthPct,
                'color' => $color,
            ];
        }

        return [
            'labels' => $monthsLabels,
            'years' => $years,
            'datasets' => $datasets,
            'yearly_totals' => $yearlyTotals,
        ];
    }

    /**
     * Get monthly revenue data for charts
     */
    public function getMonthlyRevenue($year)
    {
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $revenue = Ordine::whereYear('dataOrdine', $year)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2)
                ->sum('totaleOrdine');
            
            $data[] = [
                'month' => self::MONTHS_IT[$month],
                'revenue' => round($revenue, 2),
            ];
        }
        return $data;
    }

    /**
     * Get daily revenue for current month
     */
    public function getDailyRevenue($year, $month)
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $data = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            
            $revenue = Ordine::whereDate('dataOrdine', $date)
                ->where('statoOrdine', 2)
                ->sum('totaleOrdine');
            
            $orders = Ordine::whereDate('dataOrdine', $date)
                ->where('statoOrdine', 2)
                ->count();

            $tickets = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                ->whereDate('ordini.dataOrdine', $date)
                ->where('ordini.statoOrdine', 2)
                ->sum('righe_ordine.quantitaIngressoOrdine');

            $data[] = [
                'day' => $day,
                'date' => $date,
                'revenue' => round($revenue, 2),
                'orders' => $orders,
                'tickets' => (int)$tickets,
            ];
        }

        return $data;
    }

    /**
     * Get top selling locations
     */
    public function getTopLocations($year, $limit = 10, ?string $period = 'tutto_anno', ?string $customFrom = null, ?string $customTo = null)
    {
        $range = self::resolvePeriodRange($year, $period, $customFrom, $customTo);
        return RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->whereBetween('ordini.dataOrdine', [$range['from'], $range['to']])
            ->where('ordini.statoOrdine', 2)
            ->select([
                'location.idLocation',
                'location.nomeLocation',
                DB::raw('SUM(righe_ordine.quantitaIngressoOrdine) as total_tickets'),
                DB::raw('SUM(righe_ordine.prezzoIngressoOrdine * righe_ordine.quantitaIngressoOrdine) as total_revenue'),
            ])
            ->groupBy('location.idLocation', 'location.nomeLocation')
            ->orderByDesc('total_tickets')
            ->limit($limit)
            ->get();
    }

    private function _unusedLegacyTopLocations($year, $limit = 10)
    {
        return RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->whereNotNull('ordini.dataOrdine')
            ->where('ordini.statoOrdine', 2)
            ->select([
                'location.idLocation',
                'location.nomeLocation',
                DB::raw('SUM(righe_ordine.quantitaIngressoOrdine) as total_tickets'),
                DB::raw('SUM(righe_ordine.prezzoIngressoOrdine * righe_ordine.quantitaIngressoOrdine) as total_revenue'),
            ])
            ->groupBy('location.idLocation', 'location.nomeLocation')
            ->orderByDesc('total_tickets')
            ->limit($limit)
            ->get();
    }

    /**
     * Get revenue by payment method
     */
    public function getRevenueByPaymentMethod($year)
    {
        return Ordine::join('tipo_pagamento', 'ordini.tipoPagamentoOrdine', '=', 'tipo_pagamento.idTipoPagamento')->whereYear('ordini.dataOrdine', (int)$year)
            ->whereNotNull('ordini.dataOrdine')
            ->where('ordini.statoOrdine', 2)
            ->select([
                'tipo_pagamento.nomePagamento',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(ordini.totaleOrdine) as total_revenue'),
            ])
            ->groupBy('tipo_pagamento.idTipoPagamento', 'tipo_pagamento.nomePagamento')
            ->get();
    }

    /**
     * Compare two years
     */
    public function compareYears($year1, $year2)
    {
        $data = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $revenue1 = Ordine::whereYear('dataOrdine', $year1)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2)
                ->sum('totaleOrdine');
            
            $revenue2 = Ordine::whereYear('dataOrdine', $year2)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2)
                ->sum('totaleOrdine');
            
            $data[] = [
                'month' => self::MONTHS_IT[$month],
                'year1' => round($revenue1, 2),
                'year2' => round($revenue2, 2),
            ];
        }
        
        return $data;
    }

    /**
     * Get revenue for the last N years
     */
    public function getRevenueLastNYears($years = 5)
    {
        $currentYear = date('Y');
        $data = [];
        for ($i = $years - 1; $i >= 0; $i--) {
            $year = $currentYear - $i;
            $revenue = $this->getTotalRevenue($year);
            $data[] = [
                'year' => (string)$year,
                'revenue' => $revenue,
            ];
        }
        return $data;
    }

    /**
     * Get Year-To-Date (YTD) Revenue for a specific year
     */
    public function getYtdRevenue($year)
    {
        $currentYear = date('Y');
        $dateLimit = $year == $currentYear ? now() : now()->subYears($currentYear - $year);
        
        return Ordine::whereYear('dataOrdine', $year)
            ->where('dataOrdine', '<=', $dateLimit)
            ->where('statoOrdine', 2)
            ->sum('totaleOrdine');
    }

    /**
     * Get Year-To-Date (YTD) Tickets for a specific year
     */
    public function getYtdTickets($year)
    {
        $currentYear = date('Y');
        $dateLimit = $year == $currentYear ? now() : now()->subYears($currentYear - $year);
        
        return RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereNotNull('ordini.dataOrdine')
            ->where('ordini.dataOrdine', '<=', $dateLimit)->whereYear('ordini.dataOrdine', (int)$year)
            ->where('ordini.statoOrdine', 2)
            ->sum('righe_ordine.quantitaIngressoOrdine');
    }

    /**
     * Get standalone cash flow summary for year
     */
    public function getCashFlowStats($year = null)
    {
        $year = $year ?? date('Y');
        
        $entrate = \App\Models\EntrataUscita::whereYear('dataOperazione', $year)
            ->where('tipoOperazione', 'E')
            ->sum('importo');

        $uscite = \App\Models\EntrataUscita::whereYear('dataOperazione', $year)
            ->where('tipoOperazione', 'U')
            ->sum('importo');
            
        return [
            'entrate' => round($entrate, 2),
            'uscite' => round($uscite, 2),
            'saldo' => round($entrate - $uscite, 2),
        ];
    }

    /**
     * Helper to apply city filter on queries joining or starting from ordini
     */
    protected function applyCityFilter($query, ?string $city, string $ordiniTable = 'ordini'): void
    {
        if (empty($city)) {
            return;
        }

        $locationIds = Location::query()
            ->when(strtolower($city) === 'roma', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('cittaLocation', 'Roma')
                        ->orWhereNull('cittaLocation')
                        ->orWhere('cittaLocation', '');
                });
            }, function ($q) use ($city) {
                $q->where('cittaLocation', $city);
            })
            ->pluck('idLocation')
            ->toArray();

        $query->whereIn("{$ordiniTable}.idLocationOrdine", $locationIds);
    }

    /**
     * Get stats for a custom date range period
     */
    public function getPeriodStats($from, $to, ?string $city = null)
    {
        $revQuery = Ordine::whereBetween('dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('statoOrdine', 2);
        $this->applyCityFilter($revQuery, $city, 'ordini');
        $revenue = $revQuery->sum('totaleOrdine');

        $ordQuery = Ordine::whereBetween('dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('statoOrdine', 2);
        $this->applyCityFilter($ordQuery, $city, 'ordini');
        $orders = $ordQuery->count();

        $tktQuery = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereBetween('ordini.dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('ordini.statoOrdine', 2);
        $this->applyCityFilter($tktQuery, $city, 'ordini');
        $tickets = $tktQuery->sum('righe_ordine.quantitaIngressoOrdine');

        return [
            'revenue' => round($revenue, 2),
            'orders' => $orders,
            'tickets' => (int)$tickets,
        ];
    }

    /**
     * Get daily stats for a date range period
     */
    public function getPeriodDailyStats($from, $to, ?string $city = null)
    {
        $dates = [];
        $current = strtotime($from);
        $last = strtotime($to);

        while ($current <= $last) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        $ordersQuery = Ordine::whereBetween('dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('statoOrdine', 2);
        $this->applyCityFilter($ordersQuery, $city, 'ordini');

        $orders = $ordersQuery->select([
                DB::raw('DATE(dataOrdine) as date'),
                DB::raw('SUM(totaleOrdine) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            ])
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $ticketsQuery = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereBetween('ordini.dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('ordini.statoOrdine', 2);
        $this->applyCityFilter($ticketsQuery, $city, 'ordini');

        $tickets = $ticketsQuery->select([
                DB::raw('DATE(ordini.dataOrdine) as date'),
                DB::raw('SUM(righe_ordine.quantitaIngressoOrdine) as tickets_count')
            ])
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $data = [];
        foreach ($dates as $date) {
            $orderData = $orders->get($date);
            $ticketData = $tickets->get($date);

            $data[$date] = [
                'revenue' => $orderData ? round($orderData->revenue, 2) : 0.0,
                'orders' => $orderData ? $orderData->orders_count : 0,
                'tickets' => $ticketData ? (int)$ticketData->tickets_count : 0,
            ];
        }

        return $data;
    }

    /**
     * Get location sales breakdown for a custom period
     */
    public function getPeriodLocationStats($from, $to, ?string $city = null)
    {
        $query = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->whereBetween('ordini.dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('ordini.statoOrdine', 2);

        $this->applyCityFilter($query, $city, 'ordini');

        return $query->select([
                'location.idLocation',
                'location.nomeLocation',
                DB::raw('SUM(righe_ordine.quantitaIngressoOrdine) as total_tickets'),
                DB::raw('SUM(righe_ordine.prezzoIngressoOrdine * righe_ordine.quantitaIngressoOrdine) as total_revenue'),
            ])
            ->groupBy('location.idLocation', 'location.nomeLocation')
            ->get()
            ->keyBy('idLocation');
    }

    /**
     * Get detailed monthly revenue and ticket count comparison for two years
     */
    public function compareYearsDetailed($year1, $year2, ?string $city = null)
    {
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $rev1Q = Ordine::whereYear('dataOrdine', $year1)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2);
            $this->applyCityFilter($rev1Q, $city, 'ordini');
            $revenue1 = $rev1Q->sum('totaleOrdine');
                
            $tkt1Q = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                ->whereYear('ordini.dataOrdine', $year1)
                ->whereMonth('ordini.dataOrdine', $month)
                ->where('ordini.statoOrdine', 2);
            $this->applyCityFilter($tkt1Q, $city, 'ordini');
            $tickets1 = $tkt1Q->sum('righe_ordine.quantitaIngressoOrdine');

            $rev2Q = Ordine::whereYear('dataOrdine', $year2)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2);
            $this->applyCityFilter($rev2Q, $city, 'ordini');
            $revenue2 = $rev2Q->sum('totaleOrdine');
                
            $tkt2Q = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                ->whereYear('ordini.dataOrdine', $year2)
                ->whereMonth('ordini.dataOrdine', $month)
                ->where('ordini.statoOrdine', 2);
            $this->applyCityFilter($tkt2Q, $city, 'ordini');
            $tickets2 = $tkt2Q->sum('righe_ordine.quantitaIngressoOrdine');

            $data[] = [
                'month' => self::MONTHS_IT[$month],
                'revenue_year1' => round($revenue1, 2),
                'tickets_year1' => (int)$tickets1,
                'revenue_year2' => round($revenue2, 2),
                'tickets_year2' => (int)$tickets2,
            ];
        }
        return $data;
    }

    /**
     * Get monthly financials for ReportFinanziari page graphs
     */
    public function getMonthlyFinancials($year)
    {
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $revenue = Ordine::whereYear('dataOrdine', $year)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2)
                ->sum('totaleOrdine');

            $righe = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                ->whereNotNull('ordini.dataOrdine')
                ->whereYear('ordini.dataOrdine', $year)->whereMonth('ordini.dataOrdine', $month)
                ->where('ordini.statoOrdine', 2)
                ->select([
                    'righe_ordine.prezzoIngressoOrdine',
                    'righe_ordine.quantitaIngressoOrdine',
                    'righe_ordine.idIngressoOrdine',
                    'righe_ordine.nomeIngressoOrdine',
                    'ordini.idLocationOrdine',
                ])
                ->get();

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

            $commissions = 0;
            foreach ($righe as $riga) {
                $subtotal = (float)$riga->prezzoIngressoOrdine * (int)$riga->quantitaIngressoOrdine;
                
                $tipologia = $tipologieByCont[$riga->idIngressoOrdine]
                    ?? $tipologieById[$riga->idIngressoOrdine]
                    ?? ($tipologieByNameLoc[trim($riga->nomeIngressoOrdine) . '_' . $riga->idLocationOrdine] ?? null)
                    ?? ($tipologieByName[trim($riga->nomeIngressoOrdine)] ?? null);

                $tipoProvvigione = $tipologia ? (int)$tipologia->tipoProvvigione : 0;
                $valoreProvvigione = $tipologia ? (float)$tipologia->valoreProvvigione : 0.0;

                if ($tipoProvvigione == 1) {
                    $commissions += ($subtotal / 100) * $valoreProvvigione;
                } else {
                    $commissions += $valoreProvvigione * (int)$riga->quantitaIngressoOrdine;
                }
            }

            $spettanteLocation = round(max(0, $revenue - $commissions), 2);

            // Calcolo provvigioni partner per il mese
            $idPartnerGestore = (int)\App\Models\Setting::getVal('id_partner_gestore', 5);
            $partnerRighe = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                ->whereNotNull('ordini.dataOrdine')
                ->whereYear('ordini.dataOrdine', $year)->whereMonth('ordini.dataOrdine', $month)
                ->where('ordini.statoOrdine', 2)
                ->whereNotNull('ordini.idPartnerOrdine')
                ->where('ordini.idPartnerOrdine', '>', 0)
                ->where('ordini.idPartnerOrdine', '!=', $idPartnerGestore)
                ->whereIn('ordini.idPartnerOrdine', Partner::idsConProvvigione())
                ->select([
                    DB::raw('COALESCE(righe_ordine.prezzoIngressoOrdine, righe_ordine.prezzoIngressoordine, 0) as prezzoIngressoOrdine'),
                    'righe_ordine.quantitaIngressoOrdine',
                    'righe_ordine.idIngressoOrdine',
                    'righe_ordine.nomeIngressoOrdine',
                    'ordini.idLocationOrdine',
                    'ordini.idPartnerOrdine',
                ])
                ->get();

            $partnerCommissions = 0.0;
            $tipiPartnerMese = Partner::whereIn('idPartner', $partnerRighe->pluck('idPartnerOrdine')->unique()->filter())
                ->pluck('tipoPartner', 'idPartner');

            foreach ($partnerRighe as $riga) {
                $subtotal = (float)$riga->prezzoIngressoOrdine * (int)$riga->quantitaIngressoOrdine;
                $tipologia = $tipologieByCont[$riga->idIngressoOrdine]
                    ?? $tipologieById[$riga->idIngressoOrdine]
                    ?? ($tipologieByNameLoc[trim($riga->nomeIngressoOrdine) . '_' . $riga->idLocationOrdine] ?? null)
                    ?? ($tipologieByName[trim($riga->nomeIngressoOrdine)] ?? null);

                $tipoProv = $tipologia ? (int)$tipologia->tipoProvvigionePartner : 0;
                $valoreProv = $tipologia ? (float)$tipologia->valoreProvvigionePartner : 0.0;

                $partnerCommissions += Partner::calcolaImportoProvvigione(
                    $tipiPartnerMese[$riga->idPartnerOrdine] ?? Partner::TIPO_DIRETTO,
                    $subtotal,
                    (int) $riga->quantitaIngressoOrdine,
                    $tipoProv,
                    $valoreProv
                );
            }

            $entrate = (float)\App\Models\EntrataUscita::whereYear('dataOperazione', $year)
                ->whereMonth('dataOperazione', $month)
                ->where('tipoOperazione', 'E')
                ->sum('importo');

            $uscite = (float)\App\Models\EntrataUscita::whereYear('dataOperazione', $year)
                ->whereMonth('dataOperazione', $month)
                ->where('tipoOperazione', 'U')
                ->sum('importo');

            // Utile Netto Reale = Nostra Provvigione Biglietti - Provvigioni Partner + Entrate Extra - Uscite Extra
            $profit = round($commissions - $partnerCommissions + $entrate - $uscite, 2);

            $data[] = [
                'month' => self::MONTHS_IT[$month],
                'revenue' => round($revenue, 2),
                'commissions' => round($commissions, 2),
                'partner_commissions' => round($partnerCommissions, 2),
                'spettante_location' => $spettanteLocation,
                'margin' => round($commissions, 2),
                'entrate' => round($entrate, 2),
                'uscite' => round($uscite, 2),
                'profit' => $profit,
            ];
        }
        return $data;
    }
}
