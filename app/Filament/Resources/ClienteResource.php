<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clienti';
    protected static ?string $slug = 'clienti';
    protected static ?string $navigationGroup = 'Gestione';
    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
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
                Forms\Components\TextInput::make('nome')
                    ->readonly(),
                Forms\Components\TextInput::make('cognome')
                    ->readonly(),
                Forms\Components\TextInput::make('email')
                    ->readonly(),
                Forms\Components\TextInput::make('telefono')
                    ->readonly(),
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
                    ->dateTime('d/m/Y H:i')
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
                                    number_format($cliente->totale_speso, 2, ',', '.'),
                                    $cliente->ultimo_ordine ? $cliente->ultimo_ordine->format('d/m/Y H:i') : '',
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
                    ])),
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
        ];
    }
}
