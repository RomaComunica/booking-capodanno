<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clienti';
    protected static ?string $slug = 'clienti';
    protected static ?string $navigationGroup = 'Gestione';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return (string) Cliente::count();
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dati Cliente')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cognome')
                            ->label('Cognome')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telefono')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('citta')
                            ->label('Città')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('cognome')
                    ->label('Cognome')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Telefono')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('numero_ordini')
                    ->label('Ordini')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('totale_speso')
                    ->label('Totale Speso')
                    ->money('EUR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('ultimo_ordine')
                    ->label('Ultimo Ordine')
                    ->formatStateUsing(function ($state, Cliente $record): string {
                        if ((int) $record->numero_ordini === 0 || !$state) {
                            return '—';
                        }
                        return $state instanceof \Carbon\CarbonInterface
                            ? $state->format('d/m/Y H:i')
                            : date('d/m/Y H:i', strtotime((string) $state));
                    })
                    ->sortable(),
            ])
            ->defaultSort('ultimo_ordine', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Esporta CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $clienti = Cliente::all();
                        
                        $callback = function() use ($clienti) {
                            $file = fopen('php://output', 'w');
                            
                            // Add BOM for Excel compatibility
                            fputs($file, "\xEF\xBB\xBF");
                            
                            // Headers
                            fputcsv($file, ['Nome', 'Cognome', 'Email', 'Telefono', 'Num. Ordini', 'Totale Speso (€)', 'Ultimo Ordine'], ';');
                            
                            foreach ($clienti as $cliente) {
                                fputcsv($file, [
                                    $cliente->nome,
                                    $cliente->cognome,
                                    $cliente->email,
                                    $cliente->telefono,
                                    $cliente->numero_ordini,
                                    number_format((float) $cliente->totale_speso, 2, ',', '.'),
                                    ((int) $cliente->numero_ordini > 0 && $cliente->ultimo_ordine) ? $cliente->ultimo_ordine->format('d/m/Y H:i') : '',
                                ], ';');
                            }
                            
                            fclose($file);
                        };
                        
                        return response()->stream($callback, 200, [
                            "Content-Type" => "text/csv",
                            "Content-Disposition" => "attachment; filename=clienti-export-" . date('Y-m-d') . ".csv",
                            "Pragma" => "no-cache",
                            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                            "Expires" => "0"
                        ]);
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('vedi_ordini')
                    ->label('Vedi Ordini')
                    ->icon('heroicon-o-shopping-cart')
                    ->url(fn (Cliente $record) => \App\Filament\Resources\OrdineResource::getUrl('index', [
                        'tableFilters' => [
                            'ricerca_libera' => [
                                'search' => $record->email,
                            ],
                        ],
                    ]))
                    ->visible(fn (Cliente $record): bool => (int) $record->numero_ordini > 0),

                Tables\Actions\Action::make('modifica_manuale')
                    ->label('Modifica')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (Cliente $record): bool => (int) $record->numero_ordini === 0)
                    ->form(fn (Cliente $record): array => [
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->default($record->nome),
                        Forms\Components\TextInput::make('cognome')
                            ->label('Cognome')
                            ->default($record->cognome),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->default($record->email),
                        Forms\Components\TextInput::make('telefono')
                            ->label('Telefono')
                            ->tel()
                            ->default($record->telefono),
                    ])
                    ->action(function (Cliente $record, array $data): void {
                        DB::table('clienti')
                            ->whereRaw('LOWER(TRIM(emailCliente)) = ?', [strtolower(trim($record->email))])
                            ->update([
                                'nomeCliente' => trim((string) ($data['nome'] ?? '')),
                                'cognomeCliente' => trim((string) ($data['cognome'] ?? '')),
                                'emailCliente' => strtolower(trim((string) ($data['email'] ?? ''))),
                                'telefonoCliente' => trim((string) ($data['telefono'] ?? '')),
                                'updated_at' => now(),
                            ]);

                        Notification::make()
                            ->title('Cliente aggiornato')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('elimina_manuale')
                    ->label('Elimina')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Cliente $record): bool => (int) $record->numero_ordini === 0)
                    ->action(function (Cliente $record): void {
                        DB::table('clienti')
                            ->whereRaw('LOWER(TRIM(emailCliente)) = ?', [strtolower(trim($record->email))])
                            ->delete();

                        Notification::make()
                            ->title('Cliente eliminato')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // 
            ]);
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
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
        ];
    }
}
