<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\StatisticsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class ReportFinanziari extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Report Finanziari';
    protected static ?string $title = 'Bilancio e Utile Netto';
    protected static ?string $navigationGroup = 'Gestione Commerciale';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.report-finanziari';

    public ?array $data = [];
    public array $stats = [];
    public array $monthlyFinancials = [];

    public function mount(): void
    {
        $defaultYear = \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        
        $this->form->fill([
            'year' => $defaultYear,
        ]);
        
        $this->loadStats($defaultYear);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('year')
                    ->label('Anno Riferimento')
                    ->options(function () {
                        $years = [];
                        for ($y = date('Y') + 1; $y >= 2020; $y--) {
                            $years[(string)$y] = $y;
                        }
                        return $years;
                    })
                    ->default(\App\Models\Setting::getVal('anno_riferimento', date('Y')))
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->loadStats($state);
                    }),
            ])
            ->statePath('data');
    }

    protected function loadStats($year)
    {
        $service = app(StatisticsService::class);
        $dashboardStats = $service->getDashboardStats($year);
        $cashFlowStats = $service->getCashFlowStats($year);
        
        $this->stats = [
            'fatturato_lordo' => $dashboardStats['total_revenue'] ?? 0,
            'provvigioni_trattenute' => $dashboardStats['total_commissions'] ?? $dashboardStats['net_profit_tickets'] ?? 0,
            'margine_operativo' => $dashboardStats['total_margin'] ?? (($dashboardStats['total_revenue'] ?? 0) - ($dashboardStats['net_profit_tickets'] ?? 0)),
            'entrate_extra' => $cashFlowStats['entrate'] ?? 0,
            'uscite_extra' => $cashFlowStats['uscite'] ?? 0,
            'utile_netto' => $dashboardStats['total_profit'] ?? $dashboardStats['profit_after_expenses'] ?? 0,
        ];

        $this->monthlyFinancials = $service->getMonthlyFinancials($year);
        $this->dispatch('draw-financial-charts', year: $year, data: $this->monthlyFinancials);
    }
}
