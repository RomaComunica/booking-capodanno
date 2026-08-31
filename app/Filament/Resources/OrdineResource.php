<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdineResource\Pages;
use App\Models\Ordine;
use App\Mail\OrderConfirmation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class OrdineResource extends Resource
{
    protected static ?string $model = Ordine::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationLabel = 'Ordini';
    
    protected static ?string $modelLabel = 'Ordine';
    
    protected static ?string $pluralModelLabel = 'Ordini';

    protected static ?string $slug = 'ordini';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dettagli Ordine')
                    ->schema([
                        Forms\Components\Select::make('idLocationOrdine')
                            ->label('Location')
                            ->options(function () {
                                return \App\Models\Location::orderBy('nomeLocation')->get()->mapWithKeys(function ($loc) {
                                    $suffix = $loc->attivaLocation ? '' : ' (Non attiva)';
                                    return [$loc->idLocation => $loc->nomeLocation . $suffix];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->live(),
                        
                        Forms\Components\Select::make('idPartnerOrdine')
                            ->label('Partner')
                            ->relationship('partner', 'nomePartner')
                            ->searchable(),
                        
                        Forms\Components\Select::make('statoOrdine')
                            ->label('Stato Ordine')
                            ->options([
                                0 => 'In corso (In attesa)',
                                1 => 'Annullato',
                                2 => 'Completato',
                            ])
                            ->required()
                            ->default(2),
                        
                        Forms\Components\Select::make('tipoPagamentoOrdine')
                            ->label('Metodo Pagamento')
                            ->relationship('tipoPagamento', 'nomePagamento')
                            ->required()
                            ->default(2)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ((int)$state === 3) {
                                    $set('tipoSpedizioneOrdine', 4);
                                }
                            }),
                        
                        Forms\Components\Select::make('tipoSpedizioneOrdine')
                            ->label('Tipo Spedizione')
                            ->options(function (Forms\Get $get) {
                                $tipoPagamento = (int)$get('tipoPagamentoOrdine');
                                $query = \App\Models\TipoSpedizione::query();
                                if ($tipoPagamento === 3) {
                                    $query->where(function($q) {
                                        $q->where('idSpedizione', 4)
                                          ->orWhere('nomeSpedizione', 'like', '%ritiro%')
                                          ->orWhere('descrizioneSpedizione', 'like', '%ritiro%');
                                    });
                                }
                                return $query->pluck('nomeSpedizione', 'idSpedizione');
                            })
                            ->default(1)
                            ->required(),

                        Forms\Components\DateTimePicker::make('dataOrdine')
                            ->label('Data Ordine')
                            ->default(now())
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Informazioni Cliente & Tavolo')
                    ->schema([
                        Forms\Components\TextInput::make('nomeClienteOrdine')
                            ->label('Nome')
                            ->required(),
                        
                        Forms\Components\TextInput::make('cognomeClienteOrdine')
                            ->label('Cognome')
                            ->required(),
                        
                        Forms\Components\TextInput::make('emailClienteOrdine')
                            ->label('Email')
                            ->email()
                            ->required(),
                        
                        Forms\Components\TextInput::make('telefonoClienteOrdine')
                            ->label('Telefono')
                            ->tel(),
                        
                        Forms\Components\TextInput::make('tavolo')
                            ->label('Tavolo Assegnato')
                            ->placeholder('Es. Rossi / Tavolo 5')
                            ->maxLength(100),

                        Forms\Components\Textarea::make('noteOrdine')
                            ->label('Note Ordine')
                            ->rows(2)
                            ->columnSpan(2),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Ticket Acquistati')
                    ->description('Seleziona i biglietti disponibili per la location scelta')
                    ->schema([
                        Forms\Components\Repeater::make('righe')
                            ->relationship('righe')
                            ->schema([
                                Forms\Components\Select::make('idIngressoOrdine')
                                    ->label('Biglietto / Ingresso')
                                    ->options(function (Forms\Get $get) {
                                        $locationId = $get('../../idLocationOrdine');
                                        $query = \App\Models\TipologiaIngresso::query();
                                        if ($locationId) {
                                            $query->where('idLocation', $locationId);
                                        }
                                        return $query->orderBy('nomeIngresso')->get()->mapWithKeys(function ($t) {
                                            return [$t->idIngressoCont => $t->nomeIngresso . ' (€' . number_format($t->prezzoIngresso, 2, ',', '.') . ')'];
                                        });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $tipologia = \App\Models\TipologiaIngresso::find($state);
                                            if ($tipologia) {
                                                $set('nomeIngressoOrdine', $tipologia->nomeIngresso);
                                                $set('prezzoIngressoOrdine', (float)$tipologia->prezzoIngresso);
                                                $qty = (int)($get('quantitaIngressoOrdine') ?: 1);
                                                $set('totaleRiga', (float)$tipologia->prezzoIngresso * $qty);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\Hidden::make('nomeIngressoOrdine')
                                    ->required(),

                                Forms\Components\TextInput::make('quantitaIngressoOrdine')
                                    ->label('Quantità')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $price = (float)($get('prezzoIngressoOrdine') ?: 0);
                                        $qty = (int)($state ?: 1);
                                        $set('totaleRiga', $price * $qty);
                                    }),

                                Forms\Components\TextInput::make('prezzoIngressoOrdine')
                                    ->label('Prezzo Unit.')
                                    ->numeric()
                                    ->prefix('€')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\TextInput::make('totaleRiga')
                                    ->label('Totale Riga')
                                    ->numeric()
                                    ->prefix('€')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(5)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $righe = $get('righe') ?? [];
                                $total = 0;
                                foreach ($righe as $riga) {
                                    $price = (float)($riga['prezzoIngressoOrdine'] ?? 0);
                                    $qty = (int)($riga['quantitaIngressoOrdine'] ?? 1);
                                    $total += ($price * $qty);
                                }
                                $set('totaleOrdine', $total);
                            }),

                        Forms\Components\TextInput::make('totaleOrdine')
                            ->label('Totale Complessivo Ordine')
                            ->numeric()
                            ->prefix('€')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numeroOrdine')
                    ->label('N. Ordine')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Numero ordine copiato'),
                
                Tables\Columns\BadgeColumn::make('statoOrdine')
                    ->label('Stato')
                    ->formatStateUsing(fn ($state) => match ((int)$state) {
                        0 => 'In corso',
                        1 => 'Annullato',
                        2 => 'Completato',
                        default => 'Sconosciuto',
                    })
                    ->colors([
                        'warning' => fn ($state) => (int)$state === 0,
                        'danger' => fn ($state) => (int)$state === 1,
                        'success' => fn ($state) => (int)$state === 2,
                    ]),
                
                Tables\Columns\TextColumn::make('dataOrdine')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('location.nomeLocation')
                    ->label('Location')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('nomeClienteOrdine')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => 
                        $record->nomeClienteOrdine . ' ' . $record->cognomeClienteOrdine
                    )
                    ->searchable(['nomeClienteOrdine', 'cognomeClienteOrdine'])
                    ->limit(25),
                
                Tables\Columns\TextColumn::make('emailClienteOrdine')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('totaleOrdine')
                    ->label('Totale')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipoPagamento.nomePagamento')
                    ->label('Metodo Pagamento')
                    ->badge()
                    ->default('Bonifico Bancario')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('dataPagamentoOrdineFornitore')
                    ->label('Pagata Location')
                    ->boolean()
                    ->getStateUsing(fn ($record) => 
                        $record->dataPagamentoOrdineFornitore && $record->dataPagamentoOrdineFornitore !== '0000-00-00 00:00:00'
                    )
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->action(function ($record) {
                        $isPaid = $record->dataPagamentoOrdineFornitore && $record->dataPagamentoOrdineFornitore !== '0000-00-00 00:00:00';
                        $record->update([
                            'dataPagamentoOrdineFornitore' => $isPaid ? null : now(),
                        ]);
                        Notification::make()
                            ->title($isPaid ? 'Pagamento Location contrassegnato come DA PAGARE' : 'Pagamento Location contrassegnato come PAGATO')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Clicca per cambiare lo stato di pagamento alla location'),
                
                Tables\Columns\TextColumn::make('noteOrdine')
                    ->label('Note')
                    ->limit(20)
                    ->placeholder('—')
                    ->tooltip(fn ($record) => $record->noteOrdine ?: null)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tavolo')
                    ->label('Tavolo')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->limit(15)
                    ->tooltip(fn ($record) => $record->tavolo ? 'Tavolo: ' . $record->tavolo . ' (Clicca per modificare)' : 'Clicca per assegnare un tavolo')
                    ->action(
                        Tables\Actions\Action::make('modifica_tavolo')
                            ->modalHeading(fn ($record) => 'Assegna / Modifica Tavolo (Ordine #' . $record->numeroOrdine . ')')
                            ->modalDescription(fn ($record) => 'Cliente: ' . $record->nomeClienteOrdine . ' ' . $record->cognomeClienteOrdine . ' | ' . ($record->location?->nomeLocation ?? ''))
                            ->form([
                                Forms\Components\TextInput::make('tavolo')
                                    ->label('Nome o Numero Tavolo')
                                    ->placeholder('Es. Rossi, Tavolo 5, ecc.')
                                    ->maxLength(100)
                                    ->default(fn ($record) => $record->tavolo),
                            ])
                            ->action(function ($record, array $data) {
                                $record->update(['tavolo' => trim($data['tavolo'] ?? '')]);
                                Notification::make()
                                    ->title('Tavolo aggiornato con successo')
                                    ->success()
                                    ->send();
                            })
                    ),
            ])
            ->filters([
                // Ricerca libera
                Filter::make('ricerca_libera')
                    ->form([
                        Forms\Components\TextInput::make('search')
                            ->label('Ricerca libera')
                            ->placeholder('Nome, cognome, email, telefono, numero ordine...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['search'] ?? null,
                            fn ($q, $search) => $q->where(function ($q) use ($search) {
                                $q->where('nomeClienteOrdine', 'like', "%{$search}%")
                                  ->orWhere('cognomeClienteOrdine', 'like', "%{$search}%")
                                  ->orWhere('emailClienteOrdine', 'like', "%{$search}%")
                                  ->orWhere('telefonoClienteOrdine', 'like', "%{$search}%")
                                  ->orWhere('numeroOrdine', 'like', "%{$search}%");
                            })
                        );
                    }),
                
                // Stato ordine
                SelectFilter::make('statoOrdine')
                    ->label('Stato')
                    ->options([
                        '0' => 'In corso',
                        '1' => 'Annullato',
                        '2' => 'Completato',
                    ]),
                
                // Tipo pagamento
                SelectFilter::make('tipoPagamentoOrdine')
                    ->label('Tipo Pagamento')
                    ->options(\App\Models\TipoPagamento::pluck('nomePagamento', 'idTipoPagamento')),
                
                // Tipo spedizione
                SelectFilter::make('tipoSpedizioneOrdine')
                    ->label('Tipo Spedizione')
                    ->options(\App\Models\TipoSpedizione::pluck('nomeSpedizione', 'idSpedizione')),
                
                // Range date
                Filter::make('data_range')
                    ->form([
                        Forms\Components\DatePicker::make('data_da')
                            ->label('Dal')
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('data_a')
                            ->label('Al')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['data_da'] ?? null, fn ($q, $date) => $q->whereDate('dataOrdine', '>=', $date))
                            ->when($data['data_a'] ?? null, fn ($q, $date) => $q->whereDate('dataOrdine', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['data_da']) && empty($data['data_a'])) {
                            return null;
                        }
                        
                        if (!empty($data['data_da']) && !empty($data['data_a'])) {
                            return 'Dal ' . date('d/m/Y', strtotime($data['data_da'])) . ' al ' . date('d/m/Y', strtotime($data['data_a']));
                        }
                        
                        if (!empty($data['data_da'])) {
                            return 'Dal ' . date('d/m/Y', strtotime($data['data_da']));
                        }
                        
                        return 'Fino al ' . date('d/m/Y', strtotime($data['data_a']));
                    }),
                
                // Location
                SelectFilter::make('idLocationOrdine')
                    ->label('Location')
                    ->options(\App\Models\Location::pluck('nomeLocation', 'idLocation')),
                
                // Partner
                SelectFilter::make('idPartnerOrdine')
                    ->label('Partner')
                    ->options(\App\Models\Partner::pluck('nomePartner', 'idPartner')),
                
                // Con note
                TernaryFilter::make('con_note')
                    ->label('Con note')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo con note')
                    ->falseLabel('Solo senza note')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('noteOrdine')->where('noteOrdine', '!=', ''),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('noteOrdine')->orWhere('noteOrdine', '')),
                    ),
                
                // Pagamento location
                Filter::make('pagamento_location')
                    ->label('Pagamento Location')
                    ->form([
                        Forms\Components\Select::make('stato')
                            ->label('Stato Pagamento Location')
                            ->options([
                                'non_pagato' => 'Non pagati',
                                'pagato' => 'Pagati',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['stato'])) {
                            return $query;
                        }
                        
                        if ($data['stato'] === 'non_pagato') {
                            return $query->where(function ($q) {
                                $q->where('dataPagamentoOrdineFornitore', '0000-00-00 00:00:00')
                                  ->orWhereNull('dataPagamentoOrdineFornitore');
                            })->where('statoOrdine', '!=', 1);
                        } elseif ($data['stato'] === 'pagato') {
                            return $query->where('dataPagamentoOrdineFornitore', '!=', '0000-00-00 00:00:00')
                                         ->whereNotNull('dataPagamentoOrdineFornitore');
                        }
                        
                        return $query;
                    }),
                
                // Pagamento partner
                Filter::make('pagamento_partner')
                    ->label('Pagamento Partner')
                    ->form([
                        Forms\Components\Select::make('stato')
                            ->label('Stato Pagamento Partner')
                            ->options([
                                'non_pagato' => 'Non pagati',
                                'pagato' => 'Pagati',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['stato'])) {
                            return $query;
                        }
                        
                        if ($data['stato'] === 'non_pagato') {
                            return $query->where(function ($q) {
                                $q->where('dataPagamentoOrdinePartner', '0000-00-00 00:00:00')
                                  ->orWhereNull('dataPagamentoOrdinePartner');
                            })->where('statoOrdine', '!=', 1);
                        } elseif ($data['stato'] === 'pagato') {
                            return $query->where('dataPagamentoOrdinePartner', '!=', '0000-00-00 00:00:00')
                                         ->whereNotNull('dataPagamentoOrdinePartner');
                        }
                        
                        return $query;
                    }),
                
                // Anno
                Filter::make('anno')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->label('Anno')
                            ->options(function () {
                                $years = [];
                                for ($y = date('Y'); $y >= 2017; $y--) {
                                    $years[(string)$y] = $y;
                                }
                                return $years;
                            })
                            ->default((string)date('Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['year'] ?? null,
                            fn (Builder $query, $year): Builder => $query->whereYear('dataOrdine', $year),
                        );
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->actions([
                // Invia email voucher
                Tables\Actions\Action::make('invia_email_voucher')
                    ->label('Invia Email')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Email Destinatario')
                            ->email()
                            ->required()
                            ->default(fn ($record) => $record->emailClienteOrdine),
                        
                        Forms\Components\Textarea::make('messaggio_aggiuntivo')
                            ->label('Messaggio Aggiuntivo (opzionale)')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $voucher = $record->vouchers()->first();
                            
                            if (!$voucher) {
                                Notification::make()
                                    ->title('Errore')
                                    ->body('Nessun voucher trovato per questo ordine')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            Mail::to($data['email'])->send(
                                new OrderConfirmation($record, $voucher, $data['messaggio_aggiuntivo'] ?? null)
                            );
                            
                            Notification::make()
                                ->title('Email inviata con successo')
                                ->body('Email inviata a ' . $data['email'])
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Errore invio email')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Invia Email Voucher')
                    ->modalDescription('L\'email verrà inviata con il link al voucher'),
                
                // Visualizza Voucher
                Tables\Actions\Action::make('visualizza_voucher')
                    ->label('Voucher')
                    ->icon('heroicon-o-ticket')
                    ->color('primary')
                    ->url(fn ($record) => $record->vouchers()->first() 
                        ? \Illuminate\Support\Facades\URL::signedRoute('voucher.show', [
                            'numeroOrdine' => $record->numeroOrdine, 
                            'numeroVoucher' => $record->vouchers()->first()->numeroVoucher,
                            'idPartner' => $record->idPartnerOrdine ?? 1
                          ]) 
                        : null
                    )
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->vouchers()->exists()),
                
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Cambio stato
                    Tables\Actions\BulkAction::make('cambia_stato')
                        ->label('Cambia Stato')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('nuovo_stato')
                                ->label('Nuovo Stato')
                                ->options([
                                    0 => 'In corso',
                                    1 => 'Annullato',
                                    2 => 'Completato',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['statoOrdine' => $data['nuovo_stato']]);
                            
                            Notification::make()
                                ->title('Stato aggiornato')
                                ->body(count($records) . ' ordini aggiornati')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    
                    // Registra pagamento location
                    Tables\Actions\BulkAction::make('registra_pagamento_location')
                        ->label('Registra Pagamento Location')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update([
                                'dataPagamentoOrdineFornitore' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('Pagamenti registrati')
                                ->body(count($records) . ' pagamenti location registrati')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Registra Pagamento Location')
                        ->modalDescription('Confermi di aver pagato la location per questi ordini?')
                        ->deselectRecordsAfterCompletion(),
                    
                    // Registra pagamento partner
                    Tables\Actions\BulkAction::make('registra_pagamento_partner')
                        ->label('Registra Pagamento Partner')
                        ->icon('heroicon-o-banknotes')
                        ->color('info')
                        ->action(function ($records) {
                            $records->each->update([
                                'dataPagamentoOrdinePartner' => now(),
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Pagamenti registrati')
                                ->body(count($records) . ' pagamenti partner registrati')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Registra Pagamento Partner')
                        ->modalDescription('Confermi di aver pagato il partner per questi ordini?')
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                    \Filament\Tables\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\OrdineExporter::class),
                ]),
            ])
            ->defaultSort('numeroOrdine', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrdines::route('/'),
            'create' => Pages\CreateOrdine::route('/create'),
            'edit' => Pages\EditOrdine::route('/{record}/edit'),
        ];
    }
}
