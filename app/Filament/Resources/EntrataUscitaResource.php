<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntrataUscitaResource\Pages;
use App\Models\EntrataUscita;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;

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
                    ->datalist([
                        'Google ADS',
                        'Facebook ADS',
                        'TikTok ADS',
                        'Provvigioni PR ',
                        'Rimborso Voucher #',
                        'Rimborso Cliente ',
                        'Straordinari ',
                        'Commissioni',
                        'Commissioni POS',
                        'Commissioni Bancarie',
                        'Rinnovo Dominio ',
                        'Rinnovo ',
                    ])
                    ->helperText('Suggerimenti: Google ADS, Facebook ADS, TikTok ADS, Provvigioni PR [Nome], Rimborso [Voucher/Cliente], Straordinari [Nome], Commissioni, Rinnovo Dominio')
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
                Tables\Columns\BadgeColumn::make('categoria')
                    ->label('Categoria')
                    ->getStateUsing(function ($record) {
                        if ($record->tipoOperazione === 'E') return 'Entrata';
                        $c = strtolower($record->causale ?? '');
                        if (str_contains($c, 'google')) return 'Google ADS';
                        if (str_contains($c, 'facebook') || str_contains($c, 'meta') || str_contains($c, 'fb')) return 'Facebook ADS';
                        if (str_contains($c, 'tiktok') || str_contains($c, 'tik tok')) return 'TikTok ADS';
                        if (str_contains($c, 'provvigion') || preg_match('/\bpr\b/', $c)) return 'Provvigioni PR';
                        if (str_contains($c, 'rimbors')) return 'Rimborso';
                        if (str_contains($c, 'straordinar')) return 'Straordinari';
                        if (str_contains($c, 'commission')) return 'Commissioni';
                        if (str_contains($c, 'rinnov') || str_contains($c, 'domini')) return 'Rinnovi Domini';
                        return 'Altro';
                    })
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['Entrata', 'Commissioni']),
                        'info' => fn ($state) => in_array($state, ['Google ADS', 'TikTok ADS', 'Rinnovi Domini']),
                        'primary' => 'Facebook ADS',
                        'danger' => 'Rimborso',
                        'warning' => fn ($state) => in_array($state, ['Straordinari', 'Provvigioni PR']),
                        'gray' => 'Altro',
                    ]),
                Tables\Columns\TextColumn::make('causale')
                    ->label('Causale')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $shortCausale = \Illuminate\Support\Str::limit((string) $state, 40);
                        $hasNote = !empty(trim((string) ($record->descrizione ?? '')));
                        return $hasNote ? ($shortCausale . ' *') : $shortCausale;
                    })
                    ->tooltip(function ($record) {
                        $causale = trim((string) ($record->causale ?? ''));
                        $nota = trim((string) ($record->descrizione ?? ''));
                        $isTruncated = mb_strlen($causale) > 40;
                        if ($isTruncated && $nota !== '') {
                            return $causale . ' — Nota: ' . $nota;
                        }
                        if ($nota !== '') {
                            return $nota;
                        }
                        return $isTruncated ? $causale : null;
                    }),
                Tables\Columns\TextColumn::make('importo')
                    ->label('Importo')
                    ->money('eur')
                    ->sortable()
                    ->color(fn ($record) => $record->tipoOperazione === 'E' ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('anno')
                    ->label('Anno di riferimento')
                    ->placeholder('Tutti gli anni')
                    ->options(function () {
                        $years = [];
                        for ($y = date('Y') + 1; $y >= 2017; $y--) {
                            $years[(string)$y] = (string)$y;
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
                    ->placeholder('Tutte le operazioni')
                    ->options([
                        'E' => 'Solo Entrate',
                        'U' => 'Solo Uscite',
                    ]),

                Tables\Filters\SelectFilter::make('categoriaSpesa')
                    ->label('Categoria Causale')
                    ->placeholder('Tutte le causali')
                    ->options([
                        'google_ads' => '📢 Google ADS',
                        'facebook_ads' => '📱 Facebook ADS',
                        'tiktok_ads' => '🎵 TikTok ADS',
                        'provvigioni_pr' => '🤝 Provvigioni PR',
                        'rimborsi' => '↩️ Rimborsi (Clienti / Voucher)',
                        'straordinari' => '⏱️ Straordinari (Staff)',
                        'commissioni' => '💳 Commissioni',
                        'rinnovi' => '🌐 Rinnovi Domini',
                        'altre_uscite' => '📦 Altre Uscite',
                        'entrate' => '💰 Solo Entrate',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) return;
                        if ($data['value'] === 'google_ads') {
                            $query->where('tipoOperazione', 'U')->where('causale', 'LIKE', '%google%');
                        } elseif ($data['value'] === 'facebook_ads') {
                            $query->where('tipoOperazione', 'U')->where(function ($q) {
                                $q->where('causale', 'LIKE', '%facebook%')
                                  ->orWhere('causale', 'LIKE', '%meta%')
                                  ->orWhere('causale', 'LIKE', '%fb%');
                            });
                        } elseif ($data['value'] === 'tiktok_ads') {
                            $query->where('tipoOperazione', 'U')->where(function ($q) {
                                $q->where('causale', 'LIKE', '%tiktok%')
                                  ->orWhere('causale', 'LIKE', '%tik tok%');
                            });
                        } elseif ($data['value'] === 'provvigioni_pr') {
                            $query->where('tipoOperazione', 'U')->where(function ($q) {
                                $q->where('causale', 'LIKE', '%provvigion%')
                                  ->orWhere('causale', 'LIKE', 'pr %')
                                  ->orWhere('causale', 'LIKE', '% pr %')
                                  ->orWhere('causale', 'LIKE', '% pr');
                            });
                        } elseif ($data['value'] === 'rimborsi') {
                            $query->where('tipoOperazione', 'U')->where('causale', 'LIKE', '%rimbors%');
                        } elseif ($data['value'] === 'straordinari') {
                            $query->where('tipoOperazione', 'U')->where('causale', 'LIKE', '%straordinar%');
                        } elseif ($data['value'] === 'commissioni') {
                            $query->where('tipoOperazione', 'U')->where('causale', 'LIKE', '%commission%');
                        } elseif ($data['value'] === 'rinnovi') {
                            $query->where('tipoOperazione', 'U')->where(function ($q) {
                                $q->where('causale', 'LIKE', '%rinnov%')
                                  ->orWhere('causale', 'LIKE', '%domini%');
                            });
                        } elseif ($data['value'] === 'altre_uscite') {
                            $query->where('tipoOperazione', 'U')
                                ->where('causale', 'NOT LIKE', '%google%')
                                ->where('causale', 'NOT LIKE', '%facebook%')
                                ->where('causale', 'NOT LIKE', '%meta%')
                                ->where('causale', 'NOT LIKE', '%fb%')
                                ->where('causale', 'NOT LIKE', '%tiktok%')
                                ->where('causale', 'NOT LIKE', '%tik tok%')
                                ->where('causale', 'NOT LIKE', '%provvigion%')
                                ->where('causale', 'NOT LIKE', 'pr %')
                                ->where('causale', 'NOT LIKE', '% pr %')
                                ->where('causale', 'NOT LIKE', '% pr')
                                ->where('causale', 'NOT LIKE', '%rimbors%')
                                ->where('causale', 'NOT LIKE', '%straordinar%')
                                ->where('causale', 'NOT LIKE', '%commission%')
                                ->where('causale', 'NOT LIKE', '%rinnov%')
                                ->where('causale', 'NOT LIKE', '%domini%');
                        } elseif ($data['value'] === 'entrate') {
                            $query->where('tipoOperazione', 'E');
                        }
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->defaultSort('dataOperazione', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEntrataUscitas::route('/'),
        ];
    }
}
