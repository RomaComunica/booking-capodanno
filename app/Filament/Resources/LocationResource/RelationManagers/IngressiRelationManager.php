<?php

namespace App\Filament\Resources\LocationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IngressiRelationManager extends RelationManager
{
    protected static string $relationship = 'ingressi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('ingressoAttivo')
                    ->label('Attivo')
                    ->default(true),
                
                Forms\Components\TextInput::make('nomeIngresso')
                    ->label('Nome Ingresso')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('prezzoIngresso')
                    ->label('Prezzo')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                
                Forms\Components\TextInput::make('giacenzaBiglietti')
                    ->label('Giacenza')
                    ->numeric()
                    ->default(0),
                
                Forms\Components\Toggle::make('ingressoSenzaData')
                    ->label('Senza Data')
                    ->reactive(),
                
                Forms\Components\DatePicker::make('dataIngresso')
                    ->label('Data')
                    ->hidden(fn ($get) => $get('ingressoSenzaData')),
                
                Forms\Components\TimePicker::make('orarioIngresso')
                    ->label('Orario')
                    ->seconds(false),
                
                Forms\Components\TextInput::make('etichettaIngresso')
                    ->label('Etichetta')
                    ->maxLength(255),
                
                // Partner Association (Checkbox List)
                Forms\Components\Section::make('Partner Abilitati')
                    ->description('Seleziona i partner che possono vendere questo biglietto')
                    ->schema([
                        Forms\Components\CheckboxList::make('partner_ids')
                            ->label('Partner')
                            ->options(\App\Models\Partner::pluck('nomePartner', 'idPartner'))
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->gridDirection('row')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && $record->exists) {
                                    // Load existing partner IDs
                                    $partnerIds = \DB::table('cross_partner_ingressi')
                                        ->where('idTipologiaIngresso', $record->idIngressoCont)
                                        ->where('idLocation', $record->idLocation)
                                        ->pluck('idPartnerIngressi')
                                        ->toArray();
                                    
                                    $component->state($partnerIds);
                                }
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                // Commission fields in tabs... (rest of the file)
                Forms\Components\Tabs::make('Commissioni')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Interne')
                            ->schema([
                                Forms\Components\Select::make('tipoProvvigione')
                                    ->label('Tipo')
                                    ->options([1 => 'Percentuale', 0 => 'Fisso'])
                                    ->default(1),
                                Forms\Components\TextInput::make('valoreProvvigione')
                                    ->label('Valore')
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Forms\Components\Tabs\Tab::make('Partner')
                            ->schema([
                                Forms\Components\Select::make('tipoProvvigionePartner')
                                    ->label('Tipo')
                                    ->options([1 => 'Percentuale', 0 => 'Fisso'])
                                    ->default(1),
                                Forms\Components\TextInput::make('valoreProvvigionePartner')
                                    ->label('Valore')
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Forms\Components\Tabs\Tab::make('Rimborso')
                            ->schema([
                                Forms\Components\Toggle::make('opzioneRimborso')
                                    ->label('Attiva Rimborso'),
                                Forms\Components\TextInput::make('rimborsoValorePersona')
                                    ->label('Valore per Persona')
                                    ->numeric()
                                    ->prefix('€'),
                                Forms\Components\DateTimePicker::make('rimborsoDataScadenza')
                                    ->label('Data Scadenza'),
                                Forms\Components\Textarea::make('rimborsoTesto')
                                    ->label('Testo Rimborso')
                                    ->rows(3),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nomeIngresso')
            ->reorderable('ordine') // Enable drag-and-drop!
            ->defaultSort('ordine', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('ordine')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\IconColumn::make('ingressoAttivo')
                    ->label('Attivo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('nomeIngresso')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prezzoIngresso')
                    ->label('Prezzo')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('giacenzaBiglietti')
                    ->label('Giacenza')
                    ->sortable(),
                Tables\Columns\TextColumn::make('dataIngresso')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ingressoAttivo')
                    ->label('Attivo')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-assign next order value
                        $maxOrder = $this->getOwnerRecord()->ingressi()->max('ordine') ?? 0;
                        $data['ordine'] = $maxOrder + 10;
                        $data['idIngresso'] = $this->getOwnerRecord()->ingressi()->max('idIngresso') + 1 ?? 1;
                        
                        // Store partner ids and remove from main data
                        $this->cachedPartnerIds = $data['partner_ids'] ?? [];
                        unset($data['partner_ids']);
                        
                        return $data;
                    })
                    ->after(function ($record) {
                        // Save partner associations
                        if (isset($this->cachedPartnerIds) && is_array($this->cachedPartnerIds)) {
                            foreach ($this->cachedPartnerIds as $partnerId) {
                                \DB::table('cross_partner_ingressi')->insert([
                                    'idPartnerIngressi' => $partnerId,
                                    'idLocation' => $this->getOwnerRecord()->idLocation,
                                    'idTipologiaIngresso' => $record->idIngressoCont,
                                    'quantitaIngressi' => 0,
                                    'quantitaIngressiTerra' => 0,
                                ]);
                            }
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Store partner IDs and remove from main data
                        $this->cachedPartnerIds = $data['partner_ids'] ?? [];
                        unset($data['partner_ids']);
                        return $data;
                    })
                    ->after(function ($record) {
                        // Update partner associations
                        if (isset($this->cachedPartnerIds)) {
                            // Remove old associations
                            \DB::table('cross_partner_ingressi')
                                ->where('idTipologiaIngresso', $record->idIngressoCont)
                                ->where('idLocation', $this->getOwnerRecord()->idLocation)
                                ->delete();
                            
                            // Add new associations
                            if (is_array($this->cachedPartnerIds)) {
                                foreach ($this->cachedPartnerIds as $partnerId) {
                                    \DB::table('cross_partner_ingressi')->insert([
                                        'idPartnerIngressi' => $partnerId,
                                        'idLocation' => $this->getOwnerRecord()->idLocation,
                                        'idTipologiaIngresso' => $record->idIngressoCont,
                                        'quantitaIngressi' => 0,
                                        'quantitaIngressiTerra' => 0,
                                    ]);
                                }
                            }
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
