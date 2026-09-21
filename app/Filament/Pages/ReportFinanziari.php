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
                        for ($y = date('Y') + 1; $y >= 2017; $y--) {
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
        
        $fatturatoLordo = (float)($dashboardStats['total_revenue'] ?? 0);
        $provvigioniNostre = (float)($dashboardStats['net_profit_tickets'] ?? $dashboardStats['total_commissions'] ?? 0);
        $spettanteLocation = round(max(0, $fatturatoLordo - $provvigioniNostre), 2);
        $entrateExtra = (float)($cashFlowStats['entrate'] ?? $dashboardStats['extra_incomes'] ?? 0);
        $usciteExtra = (float)($cashFlowStats['uscite'] ?? $dashboardStats['extra_expenses'] ?? 0);
        $utileNetto = round($provvigioniNostre + $entrateExtra - $usciteExtra, 2);

        $this->stats = [
            'fatturato_lordo' => $fatturatoLordo,
            'spettante_location' => $spettanteLocation,
            'provvigioni_nostre' => $provvigioniNostre,
            'provvigioni_trattenute' => $provvigioniNostre,
            'margine_operativo' => $provvigioniNostre,
            'entrate_extra' => $entrateExtra,
            'uscite_extra' => $usciteExtra,
            'utile_netto' => $utileNetto,
        ];

        $this->monthlyFinancials = $service->getMonthlyFinancials($year);
        $this->dispatch('draw-financial-charts', year: $year, data: $this->monthlyFinancials);
    }
}
