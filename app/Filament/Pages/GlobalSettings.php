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
        // Auto-sync payment methods into database without needing SSH migrations
        try {
            \Illuminate\Support\Facades\DB::table('tipo_pagamenti')->updateOrInsert(
                ['idTipoPagamento' => 1],
                [
                    'nomePagamento' => 'PayPal (+2€ Costi Gestione)',
                    'descrizioneTipoPagamento' => 'PayPal (+2€ Costi Gestione)',
                    'descrizionePagamento' => 'Pagamento sicuro tramite PayPal (+2€ costi di gestione per biglietto)',
                    'attivo' => 1,
                ]
            );
            \Illuminate\Support\Facades\DB::table('tipo_pagamenti')->updateOrInsert(
                ['idTipoPagamento' => 2],
                [
                    'nomePagamento' => 'Bonifico Bancario',
                    'descrizioneTipoPagamento' => 'Bonifico Bancario',
                    'descrizionePagamento' => 'Bonifico bancario anticipato',
                    'attivo' => 1,
                ]
            );
            \Illuminate\Support\Facades\DB::table('tipo_pagamenti')->updateOrInsert(
                ['idTipoPagamento' => 4],
                [
                    'nomePagamento' => 'Ricarica PostePay',
                    'descrizioneTipoPagamento' => 'Ricarica PostePay',
                    'descrizionePagamento' => 'Ricarica su carta PostePay anticipata',
                    'attivo' => 1,
                ]
            );
            \Illuminate\Support\Facades\DB::table('tipo_pagamenti')->updateOrInsert(
                ['idTipoPagamento' => 5],
                [
                    'nomePagamento' => 'Carta di Credito o Poste Pay',
                    'descrizioneTipoPagamento' => 'Carta di Credito o Poste Pay',
                    'descrizionePagamento' => 'Pagamento sicuro con Carta di Credito o PostePay',
                    'attivo' => 1,
                ]
            );

            // Attach Ricarica PostePay (4) to all locations if not already present
            if (\Illuminate\Support\Facades\Schema::hasTable('cross_pagamenti_location')) {
                $locations = \Illuminate\Support\Facades\DB::table('location')->pluck('idLocation');
                foreach ($locations as $locId) {
                    \Illuminate\Support\Facades\DB::table('cross_pagamenti_location')->updateOrInsert(
                        [
                            'idLocationPagamento' => $locId,
                            'idPagamento' => 4,
                        ],
                        [
                            'pagamentoLocationAttivo' => 1,
                            'costoPagamento' => 0,
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            // Ignore if tables don't exist yet
        }

        $this->form->fill([
            'anno_riferimento' => Setting::getVal('anno_riferimento', date('Y')),
            'email_notifiche' => Setting::getVal('email_notifiche', 'info@capodannoroma.it'),
            'nome_mittente' => Setting::getVal('nome_mittente', 'Capodanno Roma'),
            'paypal_enabled' => Setting::getVal('paypal_enabled', true),
            'nexi_enabled' => Setting::getVal('nexi_enabled', true),
            'bonifico_enabled' => Setting::getVal('bonifico_enabled', true),
            'iban_bonifico' => Setting::getVal('iban_bonifico', ''),
            'intestatario_bonifico' => Setting::getVal('intestatario_bonifico', ''),
            'postepay_enabled' => Setting::getVal('postepay_enabled', true),
            'intestatario_postepay' => Setting::getVal('intestatario_postepay', ''),
            'numero_carta_postepay' => Setting::getVal('numero_carta_postepay', ''),
            'codice_fiscale_postepay' => Setting::getVal('codice_fiscale_postepay', ''),
            'contanti_enabled' => Setting::getVal('contanti_enabled', true),
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
                                    ->label('Abilita PayPal (+2€ Costi Gestione)'),
                                
                                Toggle::make('nexi_enabled')
                                    ->label('Abilita Nexi XPay (Carta di Credito o Poste Pay)'),
                                
                                Section::make('Bonifico Bancario')
                                    ->schema([
                                        Toggle::make('bonifico_enabled')
                                            ->label('Abilita Bonifico Bancario')
                                            ->columnSpanFull(),
                                        TextInput::make('intestatario_bonifico')
                                            ->label('Intestatario Conto'),
                                        TextInput::make('iban_bonifico')
                                            ->label('Coordinate IBAN'),
                                    ])
                                    ->columns(2),

                                Section::make('Ricarica PostePay')
                                    ->schema([
                                        Toggle::make('postepay_enabled')
                                            ->label('Abilita Ricarica PostePay')
                                            ->columnSpanFull(),
                                        TextInput::make('intestatario_postepay')
                                            ->label('Intestatario Carta'),
                                        TextInput::make('numero_carta_postepay')
                                            ->label('Numero Carta'),
                                        TextInput::make('codice_fiscale_postepay')
                                            ->label('Codice Fiscale'),
                                    ])
                                    ->columns(3),

                                Section::make('Pagamento in Contanti (Ritiro in Sede)')
                                    ->schema([
                                        Toggle::make('contanti_enabled')
                                            ->label('Abilita Pagamento in Contanti (Ritiro in sede)'),
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
        Setting::setVal('pagamenti', 'postepay_enabled', $data['postepay_enabled'], 'boolean');
        Setting::setVal('pagamenti', 'intestatario_postepay', $data['intestatario_postepay'], 'string');
        Setting::setVal('pagamenti', 'numero_carta_postepay', $data['numero_carta_postepay'], 'string');
        Setting::setVal('pagamenti', 'codice_fiscale_postepay', $data['codice_fiscale_postepay'], 'string');
        Setting::setVal('pagamenti', 'contanti_enabled', $data['contanti_enabled'], 'boolean');
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
