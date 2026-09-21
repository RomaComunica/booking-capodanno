<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class Dashboard extends BaseDashboard implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'year' => \App\Models\Setting::getVal('anno_riferimento', date('Y')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('year')
                    ->label('Anno di riferimento')
                    ->options(function () {
                        $years = [];
                        for ($y = date('Y') + 1; $y >= 2017; $y--) {
                            $years[(string)$y] = $y;
                        }
                        return $years;
                    })
                    ->default(\App\Models\Setting::getVal('anno_riferimento', date('Y')))
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->dispatch('year-changed', $this->data['year']);
                    }),
            ])
            ->statePath('data');
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }

}
