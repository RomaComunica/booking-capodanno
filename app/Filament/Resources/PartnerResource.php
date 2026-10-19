<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                            ->options([
                                'agenzia' => 'Agenzia',
                                'affiliato' => 'Affiliato',
                                'diretto' => 'Diretto',
                            ])
                            ->required(),
                        
                        Forms\Components\Toggle::make('attivoPartner')
                            ->label('Attivo')
                            ->default(true),
                    ])
                    ->columns(3),
                
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
            ->columns([
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
                
                Tables\Columns\BadgeColumn::make('tipoPartner')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'agenzia',
                        'success' => 'affiliato',
                        'warning' => 'diretto',
                    ]),
                
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
                    ->options([
                        'agenzia' => 'Agenzia',
                        'affiliato' => 'Affiliato',
                        'diretto' => 'Diretto',
                    ]),
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
