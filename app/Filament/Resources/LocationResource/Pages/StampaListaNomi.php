<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use App\Models\Location;
use App\Models\RigaOrdine;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class StampaListaNomi extends Page
{
    protected static string $resource = LocationResource::class;

    protected static string $view = 'filament.resources.location-resource.pages.stampa-lista-nomi';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'data_evento' => now()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('location_id')
                    ->label('Location')
                    ->options(Location::where('attivaLocation', true)->pluck('nomeLocation', 'idLocation'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->updatedFilters()),
                
                DatePicker::make('data_evento')
                    ->label('Data Evento')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->updatedFilters())
                    ->default(now()),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function updatedFilters()
    {
        // Just trigger re-render
    }

    public function getRigheProperty()
    {
        $locationId = $this->data['location_id'] ?? null;
        $dataEvento = $this->data['data_evento'] ?? null;

        if (!$locationId || !$dataEvento) {
            return [];
        }

        return RigaOrdine::query()
            ->join('ordini', 'righe_ordine.numeroOrdine', '=', 'ordini.numeroOrdine')
            ->join('tipologia_ingresso', 'righe_ordine.idIngressoOrdine', '=', 'tipologia_ingresso.idIngressoCont')
            ->leftJoin('voucher', 'ordini.numeroOrdine', '=', 'voucher.numeroOrdineVoucher')
            ->where('ordini.idLocationOrdine', $locationId)
            // Filter by order date or event date? Usually list names is for a specific event date. 
            // But tickets might have open date or specific date.
            // Let's assume we filter by the date of the event stored in the ticket or order.
            // Based on previous discussions, tickets have 'dataIngresso'.
            ->whereDate('tipologia_ingresso.dataIngresso', $dataEvento)
            ->where('ordini.statoOrdine', 2) // Completed
            ->select([
                'ordini.numeroOrdine',
                'ordini.nomeClienteOrdine',
                'ordini.cognomeClienteOrdine',
                'voucher.numeroVoucher as codiceVoucher',
                'righe_ordine.nomeIngressoOrdine',
                'righe_ordine.quantitaIngressoOrdine',
            ])
            ->orderBy('ordini.cognomeClienteOrdine')
            ->orderBy('ordini.nomeClienteOrdine')
            ->get();
    }
}
