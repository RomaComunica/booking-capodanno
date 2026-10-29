<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Partner';
    
    protected static ?string $modelLabel = 'Partner';

    public static function getNavigationBadge(): ?string
    {
        return (string) Partner::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informazioni Partner')
                    ->schema([
                        Forms\Components\TextInput::make('nomePartner')
                            ->label('Nome Partner')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('tipoPartner')
                            ->label('Tipo')
                            ->options(Partner::TIPI)
                            ->default(Partner::TIPO_DIRETTO)
                            ->required()
                            ->native(false)
                            ->helperText('Diretto: canale proprio, nessuna provvigione. Agenzia e Affiliato: usano le provvigioni indicate sugli ingressi.')
                            ->formatStateUsing(fn ($state) => Partner::normalizzaTipo($state))
                            ->dehydrateStateUsing(fn ($state) => Partner::normalizzaTipo($state)),

                        Forms\Components\TextInput::make('ordine')
                            ->label('Ordine')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('attivoPartner')
                            ->label('Attivo')
                            ->default(true),
                    ])
                    ->columns(4),
                
                Forms\Components\Section::make('Contatti')
                    ->schema([
                        Forms\Components\TextInput::make('emailPartner')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => $state ?? ''),
                        
                        Forms\Components\TextInput::make('telefonoPartner')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(50)
                            ->dehydrateStateUsing(fn ($state) => $state ?? ''),
                        
                        Forms\Components\TextInput::make('urlPartner')
                            ->label('Sito Web')
                            ->url()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => $state ?? ''),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Logo Partner')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_dummy')
                            ->label('Logo Partner (Apparirà nei Voucher e nelle E-mail)')
                            ->helperText('Formati accettati: JPG, JPEG, PNG, WebP, GIF (fino a 10 MB).')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                            ->maxSize(10240)
                            ->disk('partner_loghi')
                            ->directory('')
                            ->visibility('public')
                            ->openable()
                            ->downloadable()
                            ->getUploadedFileNameForStorageUsing(function ($file, $record): string {
                                $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
                                if ($record && $record->idPartner) {
                                    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'JPG', 'JPEG', 'PNG', 'WEBP', 'GIF'] as $oldExt) {
                                        if ($oldExt !== $ext) {
                                            @unlink(public_path("partner/loghi/{$record->idPartner}.{$oldExt}"));
                                            @unlink(storage_path("app/public/partner/loghi/{$record->idPartner}.{$oldExt}"));
                                            @unlink(public_path("storage/partner/loghi/{$record->idPartner}.{$oldExt}"));
                                        }
                                    }
                                    return $record->idPartner . '.' . $ext;
                                }
                                return 'temp_' . uniqid() . '.' . $ext;
                            }),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordine')
            ->defaultSort('ordine', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('ordine')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\IconColumn::make('attivoPartner')
                    ->label('Attivo')
                    ->boolean(),

                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->height(36)
                    ->defaultImageUrl(asset('partner/loghi/default.jpg')),
                
                Tables\Columns\TextColumn::make('nomePartner')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tipoPartner')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Partner::labelTipo($state))
                    ->color(fn ($state) => match (Partner::normalizzaTipo($state)) {
                        Partner::TIPO_AGENZIA => 'primary',
                        Partner::TIPO_AFFILIATO => 'success',
                        default => 'warning',
                    }),
                
                Tables\Columns\TextColumn::make('emailPartner')
                    ->label('Email')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('telefonoPartner')
                    ->label('Telefono')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('ordini_count')
                    ->label('Ordini')
                    ->counts('ordini')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('attivoPartner')
                    ->label('Attivo')
                    ->boolean(),
                
                Tables\Filters\SelectFilter::make('tipoPartner')
                    ->label('Tipo')
                    ->options(Partner::TIPI)
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === null || $value === '') {
                            return $query;
                        }

                        $tipo = (int) $value;
                        if ($tipo === Partner::TIPO_DIRETTO) {
                            return $query->where(function ($q) {
                                $q->where('tipoPartner', Partner::TIPO_DIRETTO)
                                    ->orWhere('tipoPartner', 0)
                                    ->orWhereNull('tipoPartner');
                            });
                        }

                        return $query->where('tipoPartner', $tipo);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generateIframe')
                    ->label('Codice Iframe')
                    ->icon('heroicon-o-code-bracket')
                    ->modalHeading('Codice di Incorporamento')
                    ->modalDescription('Copia questo codice per inserire il widget nel sito del partner.')
                    ->form([
                        Forms\Components\TextInput::make('iframe_code')
                            ->label('Codice HTML')
                            ->default(fn (Partner $record) => '<iframe id="iframenew" src="' . url('/booking?partner=' . $record->codicePartner) . '" width="100%" height="800" frameborder="0"></iframe>')
                            ->disabled()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('copy')
                                    ->icon('heroicon-m-clipboard')
                                    ->action(fn ($context, $component) => $component->getLivewire()->js("window.navigator.clipboard.writeText('{$component->getState()}')"))
                            ),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('attiva')
                        ->label('Attiva selezionati')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Attiva partner selezionati')
                        ->modalDescription('Vuoi attivare tutti i partner selezionati?')
                        ->modalSubmitActionLabel('Sì, attiva')
                        ->action(function (Collection $records): void {
                            $records->each->update(['attivoPartner' => 1]);
                            Notification::make()
                                ->title('Partner attivati')
                                ->body(count($records) . ' partner attivati con successo')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('disattiva')
                        ->label('Disattiva selezionati')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Disattiva partner selezionati')
                        ->modalDescription('Vuoi disattivare tutti i partner selezionati?')
                        ->modalSubmitActionLabel('Sì, disattiva')
                        ->action(function (Collection $records): void {
                            $records->each->update(['attivoPartner' => 0]);
                            Notification::make()
                                ->title('Partner disattivati')
                                ->body(count($records) . ' partner disattivati con successo')
                                ->warning()
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
