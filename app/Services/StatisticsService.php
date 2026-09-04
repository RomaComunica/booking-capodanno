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
    public function getDashboardStats($year = null)
    {
        $year = $year ?? \App\Models\Setting::getVal('anno_riferimento', date('Y'));

        $completedOrders = Ordine::whereYear('dataOrdine', $year)->where('statoOrdine', 2)->count();
        $pendingOrders = Ordine::whereYear('dataOrdine', $year)->where('statoOrdine', 0)->count();
        $cancelledOrders = Ordine::whereYear('dataOrdine', $year)->where('statoOrdine', 1)->count();
        $totalOrders = $completedOrders + $pendingOrders + $cancelledOrders;

        // Biglietti venduti raggruppati per metodo di pagamento con etichette uniformate e totale in €
        $payments = DB::table('tipo_pagamenti')->pluck('nomePagamento', 'idTipoPagamento')->toArray();

        $rows = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereYear('ordini.dataOrdine', $year)
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

            // Correzione inversione richiesta dal cliente tra Bonifico Bancario e Ricarica PostePay
            if ($rawName === 'Bonifico Bancario') {
                $name = 'Ricarica PostePay';
            } elseif ($rawName === 'Ricarica PostePay') {
                $name = 'Bonifico Bancario';
            } else {
                $name = $rawName;
            }

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

        $totalRevenue = $this->getTotalRevenue($year);
        $totalTickets = $this->getTotalTickets($year);
        $netProfitTickets = $this->getTotalCommissions($year);

        $extraIncomes = (float)\App\Models\EntrataUscita::whereYear('dataOperazione', $year)
            ->where('tipoOperazione', 'E')
            ->sum('importo');

        $extraExpenses = (float)\App\Models\EntrataUscita::whereYear('dataOperazione', $year)
            ->where('tipoOperazione', 'U')
            ->sum('importo');

        $profitAfterExpenses = round($netProfitTickets + $extraIncomes - $extraExpenses, 2);

        return [
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
    public function getTotalRevenue($year)
    {
        return Ordine::whereYear('dataOrdine', $year)
            ->where('statoOrdine', 2)
            ->sum('totaleOrdine');
    }

    /**
     * Get total orders for year
     */
    public function getTotalOrders($year)
    {
        return Ordine::whereYear('dataOrdine', $year)->count();
    }

    /**
     * Get total tickets sold for year
     */
    public function getTotalTickets($year)
    {
        return RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereYear('ordini.dataOrdine', $year)
            ->where('ordini.statoOrdine', 2)
            ->sum('righe_ordine.quantitaIngressoOrdine');
    }

    /**
     * Calculate total commissions to partners/locations
     */
    public function getTotalCommissions($year)
    {
        $righe = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereYear('ordini.dataOrdine', $year)
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
     * Calculate total margin (revenue - commissions) without external ledger
     */
    public function getTotalMargin($year)
    {
        return round($this->getTotalRevenue($year) - $this->getTotalCommissions($year), 2);
    }

    /**
     * Calculate total net profit (margin + entrate - uscite)
     */
    public function getTotalProfit($year)
    {
        $margin = $this->getTotalMargin($year);

        // Add Entrate and subtract Uscite from the new ledger table
        $entrate = \App\Models\EntrataUscita::whereYear('dataOperazione', $year)
            ->where('tipoOperazione', 'E')
            ->sum('importo');

        $uscite = \App\Models\EntrataUscita::whereYear('dataOperazione', $year)
            ->where('tipoOperazione', 'U')
            ->sum('importo');

        return round($margin + $entrate - $uscite, 2);
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
    public function getTopLocations($year, $limit = 10)
    {
        return RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->whereYear('ordini.dataOrdine', $year)
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
        return Ordine::join('tipo_pagamento', 'ordini.tipoPagamentoOrdine', '=', 'tipo_pagamento.idTipoPagamento')
            ->whereYear('ordini.dataOrdine', $year)
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
    public function getRevenueLastNYears($years = 4)
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
            ->whereYear('ordini.dataOrdine', $year)
            ->where('ordini.dataOrdine', '<=', $dateLimit)
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
     * Get stats for a custom date range period
     */
    public function getPeriodStats($from, $to)
    {
        $revenue = Ordine::whereBetween('dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('statoOrdine', 2)
            ->sum('totaleOrdine');

        $orders = Ordine::whereBetween('dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('statoOrdine', 2)
            ->count();

        $tickets = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereBetween('ordini.dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('ordini.statoOrdine', 2)
            ->sum('righe_ordine.quantitaIngressoOrdine');

        return [
            'revenue' => round($revenue, 2),
            'orders' => $orders,
            'tickets' => (int)$tickets,
        ];
    }

    /**
     * Get daily stats for a date range period
     */
    public function getPeriodDailyStats($from, $to)
    {
        $dates = [];
        $current = strtotime($from);
        $last = strtotime($to);

        while ($current <= $last) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        $orders = Ordine::whereBetween('dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('statoOrdine', 2)
            ->select([
                DB::raw('DATE(dataOrdine) as date'),
                DB::raw('SUM(totaleOrdine) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            ])
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $tickets = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->whereBetween('ordini.dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('ordini.statoOrdine', 2)
            ->select([
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
    public function getPeriodLocationStats($from, $to)
    {
        return RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->join('location', 'ordini.idLocationOrdine', '=', 'location.idLocation')
            ->whereBetween('ordini.dataOrdine', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('ordini.statoOrdine', 2)
            ->select([
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
    public function compareYearsDetailed($year1, $year2)
    {
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $revenue1 = Ordine::whereYear('dataOrdine', $year1)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2)
                ->sum('totaleOrdine');
                
            $tickets1 = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                ->whereYear('ordini.dataOrdine', $year1)
                ->whereMonth('ordini.dataOrdine', $month)
                ->where('ordini.statoOrdine', 2)
                ->sum('righe_ordine.quantitaIngressoOrdine');

            $revenue2 = Ordine::whereYear('dataOrdine', $year2)
                ->whereMonth('dataOrdine', $month)
                ->where('statoOrdine', 2)
                ->sum('totaleOrdine');
                
            $tickets2 = RigaOrdine::join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
                ->whereYear('ordini.dataOrdine', $year2)
                ->whereMonth('ordini.dataOrdine', $month)
                ->where('ordini.statoOrdine', 2)
                ->sum('righe_ordine.quantitaIngressoOrdine');

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
                ->whereYear('ordini.dataOrdine', $year)
                ->whereMonth('ordini.dataOrdine', $month)
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

            $entrate = (float)\App\Models\EntrataUscita::whereYear('dataOperazione', $year)
                ->whereMonth('dataOperazione', $month)
                ->where('tipoOperazione', 'E')
                ->sum('importo');

            $uscite = (float)\App\Models\EntrataUscita::whereYear('dataOperazione', $year)
                ->whereMonth('dataOperazione', $month)
                ->where('tipoOperazione', 'U')
                ->sum('importo');

            // Utile Netto Reale = Nostra Provvigione Biglietti + Entrate Extra - Uscite Extra
            $profit = round($commissions + $entrate - $uscite, 2);

            $data[] = [
                'month' => self::MONTHS_IT[$month],
                'revenue' => round($revenue, 2),
                'commissions' => round($commissions, 2),
                'spettante_location' => $spettanteLocation,
                'margin' => round($commissions, 2), // Margine della nostra attività = Provvigione
                'entrate' => round($entrate, 2),
                'uscite' => round($uscite, 2),
                'profit' => $profit,
            ];
        }
        return $data;
    }
}
