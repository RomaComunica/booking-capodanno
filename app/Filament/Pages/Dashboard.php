<?php

namespace App\Filament\Pages;

use App\Services\StatisticsService;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class Dashboard extends BaseDashboard implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public ?array $data = [];

    public function mount(): void
    {
        $defaultYear = \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $defaultPeriod = StatisticsService::getDefaultDashboardPeriod();
        $range = StatisticsService::resolvePeriodRange($defaultYear, $defaultPeriod);

        $this->form->fill([
            'year' => (string) $defaultYear,
            'period' => $defaultPeriod,
            'date_from' => $range['from_date'],
            'date_to' => $range['to_date'],
        ]);
    }

    protected function notifyDashboardWidgets(): void
    {
        $year = $this->data['year'] ?? \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $period = $this->data['period'] ?? 'tutto_anno';
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;

        $this->dispatch('dashboard-filter-changed', [
            'year' => $year,
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        $this->dispatch('year-changed', $year);
    }

    public function form(Form $form): Form
    {
        $defaultYear = \App\Models\Setting::getVal('anno_riferimento', date('Y'));
        $defaultPeriod = StatisticsService::getDefaultDashboardPeriod();

        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 2,
                    'lg' => 4,
                ])->schema([
                    Select::make('year')
                        ->label('Anno di riferimento')
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
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $period = $get('period') ?: 'tutto_anno';
                            if ($period !== 'personalizzato') {
                                $range = StatisticsService::resolvePeriodRange($state, $period);
                                $set('date_from', $range['from_date']);
                                $set('date_to', $range['to_date']);
                            }
                            $this->notifyDashboardWidgets();
                        }),

                    Select::make('period')
                        ->label('Periodo / Festività')
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
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $year = $get('year') ?: \App\Models\Setting::getVal('anno_riferimento', date('Y'));
                            if ($state !== 'personalizzato') {
                                $range = StatisticsService::resolvePeriodRange($year, $state);
                                $set('date_from', $range['from_date']);
                                $set('date_to', $range['to_date']);
                            }
                            $this->notifyDashboardWidgets();
                        }),

                    DatePicker::make('date_from')
                        ->label('Dal')
                        ->visible(fn (Get $get): bool => $get('period') === 'personalizzato')
                        ->live()
                        ->afterStateUpdated(function () {
                            $this->notifyDashboardWidgets();
                        }),

                    DatePicker::make('date_to')
                        ->label('Al')
                        ->visible(fn (Get $get): bool => $get('period') === 'personalizzato')
                        ->live()
                        ->afterStateUpdated(function () {
                            $this->notifyDashboardWidgets();
                        }),
                ]),
            ])
            ->statePath('data');
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
