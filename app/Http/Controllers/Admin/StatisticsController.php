<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    protected $statsService;

    public function __construct(StatisticsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $compareYear = $request->get('compare_year');

        $data = [
            'year' => $year,
            'monthly_revenue' => $this->statsService->getMonthlyRevenue($year),
            'top_locations' => $this->statsService->getTopLocations($year),
            'payment_methods' => $this->statsService->getRevenueByPaymentMethod($year),
            'dashboard_stats' => $this->statsService->getDashboardStats($year),
        ];

        if ($compareYear) {
            $data['comparison'] = $this->statsService->compareYears($year, $compareYear);
            $data['compare_year'] = $compareYear;
        }

        return view('admin.statistics', $data);
    }

    public function daily(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        $data = [
            'year' => $year,
            'month' => $month,
            'daily_data' => $this->statsService->getDailyRevenue($year, $month),
        ];

        return view('admin.statistics-daily', $data);
    }
}
