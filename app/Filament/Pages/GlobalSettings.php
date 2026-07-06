<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GlobalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Impostazioni';
    protected static ?string $navigationLabel = 'Impostazioni Globali';
    protected static ?string $title = 'Impostazioni Globali Sistema';

    protected static string $view = 'filament.pages.global-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'anno_riferimento' => Setting::getVal('anno_riferimento', date('Y')),
            'email_notifiche' => Setting::getVal('email_notifiche', 'info@capodannoroma.it'),
            'nome_mittente' => Setting::getVal('nome_mittente', 'Capodanno Roma'),
            'paypal_enabled' => Setting::getVal('paypal_enabled', true),
            'nexi_enabled' => Setting::getVal('nexi_enabled', true),
            'bonifico_enabled' => Setting::getVal('bonifico_enabled', true),
            'iban_bonifico' => Setting::getVal('iban_bonifico', ''),
            'intestatario_bonifico' => Setting::getVal('intestatario_bonifico', ''),
            'voucher_footer_text' => Setting::getVal('voucher_footer_text', ''),
            'google_analytics_id' => Setting::getVal('google_analytics_id', ''),
            'facebook_pixel_id' => Setting::getVal('facebook_pixel_id', ''),
            'password_staff' => Setting::getVal('password_staff', 'staff2026'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Impostazioni')
                    ->tabs([
                        Tabs\Tab::make('Generali')
                            ->schema([
                                TextInput::make('anno_riferimento')
                                    ->label('Anno di Riferimento')
                                    ->numeric()
                                    ->required(),
                                
                                TextInput::make('email_notifiche')
                                    ->label('Email Notifiche Amministratore')
                                    ->email()
                                    ->required(),
                                
                                TextInput::make('nome_mittente')
                                    ->label('Nome Mittente Email')
                                    ->required(),
                                
                                TextInput::make('password_staff')
                                    ->label('Password Staff Ingressi')
                                    ->required(),
                            ]),
                        Tabs\Tab::make('Gateway Pagamento')
                            ->schema([
                                Toggle::make('paypal_enabled')
                                    ->label('Abilita PayPal'),
                                
                                Toggle::make('nexi_enabled')
                                    ->label('Abilita Nexi XPay'),
                                
                                Section::make('Bonifico Bancario')
                                    ->schema([
                                        Toggle::make('bonifico_enabled')
                                            ->label('Abilita Bonifico Bancario'),
                                        TextInput::make('intestatario_bonifico')
                                            ->label('Intestatario Conto'),
                                        TextInput::make('iban_bonifico')
                                            ->label('Coordinate IBAN'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Voucher')
                            ->schema([
                                Textarea::make('voucher_footer_text')
                                    ->label('Testo a Piè di Pagina Voucher')
                                    ->rows(4),
                            ]),
                        Tabs\Tab::make('Marketing & SEO')
                            ->schema([
                                TextInput::make('google_analytics_id')
                                    ->label('Google Analytics Event Tracker ID (es. G-XXXXXXXXXX)')
                                    ->maxLength(255)
                                    ->placeholder('G-1234567890'),
                                
                                TextInput::make('facebook_pixel_id')
                                    ->label('Meta / Facebook Pixel ID')
                                    ->maxLength(255)
                                    ->placeholder('123456789012345'),
                            ]),
                    ])
            ])
            ->statePath('data');
    }

    public function salva()
    {
        $data = $this->form->getState();

        Setting::setVal('generale', 'anno_riferimento', $data['anno_riferimento'], 'integer');
        Setting::setVal('email', 'email_notifiche', $data['email_notifiche'], 'string');
        Setting::setVal('email', 'nome_mittente', $data['nome_mittente'], 'string');
        Setting::setVal('pagamenti', 'paypal_enabled', $data['paypal_enabled'], 'boolean');
        Setting::setVal('pagamenti', 'nexi_enabled', $data['nexi_enabled'], 'boolean');
        Setting::setVal('pagamenti', 'bonifico_enabled', $data['bonifico_enabled'], 'boolean');
        Setting::setVal('pagamenti', 'iban_bonifico', $data['iban_bonifico'], 'string');
        Setting::setVal('pagamenti', 'intestatario_bonifico', $data['intestatario_bonifico'], 'string');
        Setting::setVal('voucher', 'voucher_footer_text', $data['voucher_footer_text'], 'string');
        Setting::setVal('marketing', 'google_analytics_id', $data['google_analytics_id'] ?? '', 'string');
        Setting::setVal('marketing', 'facebook_pixel_id', $data['facebook_pixel_id'] ?? '', 'string');
        Setting::setVal('generale', 'password_staff', $data['password_staff'], 'string');

        Notification::make()
            ->title('Impostazioni salvate con successo')
            ->success()
            ->send();
    }
}
