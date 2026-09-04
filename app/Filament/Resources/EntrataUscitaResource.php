<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntrataUscitaResource\Pages;
use App\Models\EntrataUscita;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EntrataUscitaResource extends Resource
{
    protected static ?string $model = EntrataUscita::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Entrate / Uscite Extra';
    protected static ?string $modelLabel = 'Movimento';
    protected static ?string $pluralModelLabel = 'Entrate e Uscite';
    protected static ?string $navigationGroup = 'Gestione Commerciale';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('dataOperazione')
                    ->label('Data Operazione')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('tipoOperazione')
                    ->label('Tipo')
                    ->options([
                        'E' => 'Entrata (+)',
                        'U' => 'Uscita (-)'
                    ])
                    ->required(),
                Forms\Components\TextInput::make('causale')
                    ->label('Causale')
                    ->maxLength(255)
                    ->required(),
                Forms\Components\TextInput::make('importo')
                    ->label('Importo (€)')
                    ->numeric()
                    ->required()
                    ->prefix('€')
                    ->minValue(0),
                Forms\Components\Textarea::make('descrizione')
                    ->label('Descrizione o Dettagli')
                    ->maxLength(65535)
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dataOperazione')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('tipoOperazione')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'E',
                        'danger' => 'U',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'E' ? 'Entrata' : 'Uscita'),
                Tables\Columns\TextColumn::make('causale')
                    ->label('Causale')
                    ->searchable(),
                Tables\Columns\TextColumn::make('importo')
                    ->label('Importo')
                    ->money('eur')
                    ->sortable()
                    ->color(fn ($record) => $record->tipoOperazione === 'E' ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('anno')
                    ->label('Anno')
                    ->options(function () {
                        $years = EntrataUscita::query()
                            ->whereNotNull('dataOperazione')
                            ->pluck('dataOperazione')
                            ->map(fn ($d) => \Carbon\Carbon::parse($d)->format('Y'))
                            ->unique()
                            ->sortDesc()
                            ->mapWithKeys(fn ($y) => [(string)$y => (string)$y])
                            ->toArray();

                        $currentYear = (string)date('Y');
                        if (!isset($years[$currentYear])) {
                            $years[$currentYear] = $currentYear;
                            krsort($years);
                        }

                        return $years;
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereYear('dataOperazione', $data['value']);
                        }
                    }),

                Tables\Filters\SelectFilter::make('tipoOperazione')
                    ->label('Tipo Operazione')
                    ->options([
                        'E' => 'Solo Entrate',
                        'U' => 'Solo Uscite',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('dataOperazione', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEntrataUscitas::route('/'),
        ];
    }
}
