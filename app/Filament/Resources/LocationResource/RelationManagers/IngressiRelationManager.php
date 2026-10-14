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
                
                Forms\Components\DatePicker::make('dataIngresso')
                    ->label('Data'),
                
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
                        Forms\Components\CheckboxList::make('partners')
                            ->label('Partner')
                            ->relationship(
                                name: 'partners',
                                titleAttribute: 'nomePartner',
                                modifyQueryUsing: fn ($query) => $query->orderBy('nomePartner')
                            )
                            ->pivotData(fn ($livewire) => [
                                'idLocation' => $livewire->getOwnerRecord()->idLocation,
                                'quantitaIngressi' => 0,
                                'quantitaIngressiTerra' => 0,
                            ])
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->gridDirection('row'),
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
                        $data['idIngresso'] = ($this->getOwnerRecord()->ingressi()->max('idIngresso') ?? 0) + 1;
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
