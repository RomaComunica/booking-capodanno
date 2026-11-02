<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use App\Models\CategoriaLocation;
use App\Models\TipologiaLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationLabel = 'Locations';
    
    protected static ?string $modelLabel = 'Location';

    public static function getNavigationBadge(): ?string
    {
        return (string) Location::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informazioni Generali')
                    ->schema([
                        Forms\Components\TextInput::make('nomeLocation')
                            ->label('Nome Location')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        
                        Forms\Components\Toggle::make('attivaLocation')
                            ->label('Attiva')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('indirizzoLocation')
                            ->label('Indirizzo')
                            ->maxLength(255)
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('telefonoLocation')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(50),
                        
                        Forms\Components\TextInput::make('emailLocation')
                            ->label('Email Notifiche')
                            ->placeholder('es: info@locale.it, prenotazioni@locale.it')
                            ->helperText('Puoi inserire più email separate da virgola.')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Riferimento')
                    ->schema([
                        Forms\Components\TextInput::make('riferimentoLocation')
                            ->label('Nome Riferimento')
                            ->maxLength(100),
                        
                        Forms\Components\TextInput::make('telefonoRifLocation')
                            ->label('Telefono Riferimento')
                            ->tel()
                            ->maxLength(50),
                        
                        Forms\Components\TextInput::make('emailRifLocation')
                            ->label('Email Riferimento')
                            ->placeholder('es: mario@locale.it, luca@locale.it')
                            ->helperText('Puoi inserire più email separate da virgola.')
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Dati Bancari')
                    ->schema([
                        Forms\Components\TextInput::make('intestatarioLocation')
                            ->label('Intestatario')
                            ->placeholder('Es. Mario Rossi S.r.l.')
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => $state ?? ''),

                        Forms\Components\TextInput::make('ibanLocation')
                            ->label('IBAN')
                            ->placeholder('Es. IT60X0542811101000000123456')
                            ->maxLength(50)
                            ->dehydrateStateUsing(fn ($state) => strtoupper(str_replace(' ', '', trim((string) $state)))),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Metodi di Pagamento')
                    ->description('Attiva o disattiva i singoli metodi di pagamento per questa location (in subordine a quanto abilitato in Impostazioni Globali).')
                    ->schema([
                        Forms\Components\Toggle::make('pagamento_5')
                            ->label('Carta di Credito o PostePay (Nexi)')
                            ->default(fn () => (bool) \App\Models\Setting::getVal('nexi_enabled', true))
                            ->helperText(fn () => \App\Models\Setting::getVal('nexi_enabled', true)
                                ? 'Attivo in Impostazioni Globali'
                                : '⚠️ Disattivato in Impostazioni Globali'),

                        Forms\Components\Toggle::make('pagamento_1')
                            ->label('PayPal (+2€ Costi Gestione)')
                            ->default(fn () => (bool) \App\Models\Setting::getVal('paypal_enabled', true))
                            ->helperText(fn () => \App\Models\Setting::getVal('paypal_enabled', true)
                                ? 'Attivo in Impostazioni Globali'
                                : '⚠️ Disattivato in Impostazioni Globali'),

                        Forms\Components\Toggle::make('pagamento_2')
                            ->label('Bonifico Bancario')
                            ->default(fn () => (bool) \App\Models\Setting::getVal('bonifico_enabled', true))
                            ->helperText(fn () => \App\Models\Setting::getVal('bonifico_enabled', true)
                                ? 'Attivo in Impostazioni Globali'
                                : '⚠️ Disattivato in Impostazioni Globali'),

                        Forms\Components\Toggle::make('pagamento_4')
                            ->label('Ricarica PostePay')
                            ->default(fn () => (bool) \App\Models\Setting::getVal('postepay_enabled', true))
                            ->helperText(fn () => \App\Models\Setting::getVal('postepay_enabled', true)
                                ? 'Attivo in Impostazioni Globali'
                                : '⚠️ Disattivato in Impostazioni Globali'),

                        Forms\Components\Toggle::make('pagamento_3')
                            ->label('Contanti (Ritiro in Sede)')
                            ->default(fn () => (bool) \App\Models\Setting::getVal('contanti_enabled', true))
                            ->helperText(fn () => \App\Models\Setting::getVal('contanti_enabled', true)
                                ? 'Attivo in Impostazioni Globali'
                                : '⚠️ Disattivato in Impostazioni Globali'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('immagineLocation')
                            ->label('Immagine / Locandina')
                            ->helperText('Formati accettati: WebP, JPG, JPEG, PNG, GIF (fino a 20 MB).')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'])
                            ->maxSize(20480)
                            ->disk('public')
                            ->directory('locations')
                            ->visibility('public')
                            ->openable()
                            ->downloadable(),
                    ])
                    ->collapsible(),
                
                /*
                Forms\Components\Section::make('Classificazione')
                    ->schema([
                        Forms\Components\Select::make('categorie')
                            ->label('Categorie')
                            ->multiple()
                            ->relationship('categorie', 'nomeCatLocation')
                            ->preload(),
                        
                        Forms\Components\Select::make('tipologie')
                            ->label('Tipologie')
                            ->multiple()
                            ->relationship('tipologie', 'nomeTipLocation')
                            ->preload(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('attivaLocation')
                    ->label('Attiva')
                    ->boolean(),
                
                Tables\Columns\ImageColumn::make('immagineLocation')
                    ->label('Foto')
                    ->disk('public')
                    ->square(),
                
                Tables\Columns\TextColumn::make('nomeLocation')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('indirizzoLocation')
                    ->label('Indirizzo')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('telefonoLocation')
                    ->label('Telefono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('emailLocation')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('ingressi_count')
                    ->label('Ingressi')
                    ->counts('ingressi')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('attivaLocation')
                    ->label('Attiva')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('attiva')
                        ->label('Attiva selezionate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Attiva location selezionate')
                        ->modalDescription('Vuoi attivare tutte le location selezionate?')
                        ->modalSubmitActionLabel('Sì, attiva')
                        ->action(function (Collection $records): void {
                            $records->each->update(['attivaLocation' => 1]);
                            Notification::make()
                                ->title('Location attivate')
                                ->body(count($records) . ' location attivate con successo')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('disattiva')
                        ->label('Disattiva selezionate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Disattiva location selezionate')
                        ->modalDescription('Vuoi disattivare tutte le location selezionate?')
                        ->modalSubmitActionLabel('Sì, disattiva')
                        ->action(function (Collection $records): void {
                            $records->each->update(['attivaLocation' => 0]);
                            Notification::make()
                                ->title('Location disattivate')
                                ->body(count($records) . ' location disattivate con successo')
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('configura_pagamenti')
                        ->label('Metodi di Pagamento')
                        ->icon('heroicon-o-credit-card')
                        ->form([
                            Forms\Components\Toggle::make('pagamento_5')
                                ->label('Carta di Credito o PostePay (Nexi)')
                                ->default(true),
                            Forms\Components\Toggle::make('pagamento_1')
                                ->label('PayPal (+2€ Costi Gestione)')
                                ->default(true),
                            Forms\Components\Toggle::make('pagamento_2')
                                ->label('Bonifico Bancario')
                                ->default(true),
                            Forms\Components\Toggle::make('pagamento_4')
                                ->label('Ricarica PostePay')
                                ->default(true),
                            Forms\Components\Toggle::make('pagamento_3')
                                ->label('Contanti (Ritiro in Sede)')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $states = [
                                1 => (bool) ($data['pagamento_1'] ?? true),
                                2 => (bool) ($data['pagamento_2'] ?? true),
                                3 => (bool) ($data['pagamento_3'] ?? true),
                                4 => (bool) ($data['pagamento_4'] ?? true),
                                5 => (bool) ($data['pagamento_5'] ?? true),
                            ];
                            foreach ($records as $record) {
                                $record->syncPagamentiLocation($states);
                            }
                            Notification::make()
                                ->title('Metodi di pagamento aggiornati per le location selezionate')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\IngressiRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
            'stampa-lista-nomi' => Pages\StampaListaNomi::route('/stampa-lista-nomi'),
            'stampa-entrate-uscite' => Pages\StampaEntrateUscite::route('/stampa-entrate-uscite'),
        ];
    }
}
