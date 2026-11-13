<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms;

class GeneratoreScript extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    
    protected static ?string $navigationLabel = 'Generatore Widget';
    
    protected static ?string $title = 'Generatore Widget Partner';
    
    protected static ?string $navigationGroup = 'Gestione Partner';

    protected static string $view = 'filament.pages.generatore-script';
    
    public ?array $data = [];
    public string $previewCode = ''; // Proprietà separata per l'anteprima
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configurazione Widget')
                    ->schema([
                        Forms\Components\Select::make('partner_id')
                            ->label('Partner *')
                            ->options(\App\Models\Partner::pluck('nomePartner', 'idPartner'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->generaCodice(['partner_id' => $state])),
                        
                        Forms\Components\Select::make('location_id')
                            ->label('Location (Opzionale)')
                            ->options(\App\Models\Location::pluck('nomeLocation', 'idLocation'))
                            ->searchable()
                            ->placeholder('Tutte le location')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->generaCodice(['location_id' => $state])),
                            
                        Forms\Components\ToggleButtons::make('tipo')
                            ->label('Tipo Widget')
                            ->options([
                                'link' => 'Link Semplice',
                                'button' => 'Pulsante Iframe',
                                'external_button' => 'Pulsante Esterno',
                                'iframe' => 'Iframe Embed',
                            ])
                            ->icons([
                                'link' => 'heroicon-o-link',
                                'button' => 'heroicon-o-cursor-arrow-rays',
                                'external_button' => 'heroicon-o-arrow-top-right-on-square',
                                'iframe' => 'heroicon-o-window',
                            ])
                            ->default('link')
                            ->inline()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->generaCodice(['tipo' => $state])),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Personalizzazione Stile')
                    ->schema([
                        Forms\Components\TextInput::make('testo_pulsante')
                            ->label('Testo Pulsante')
                            ->default('Prenota Ora')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->generaCodice(['testo_pulsante' => $state])),
                            
                        Forms\Components\ColorPicker::make('colore_sfondo')
                            ->label('Colore Sfondo')
                            ->default('#16A34A')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->generaCodice(['colore_sfondo' => $state])),
                            
                        Forms\Components\ColorPicker::make('colore_testo')
                            ->label('Colore Testo')
                            ->default('#ffffff')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->generaCodice(['colore_testo' => $state])),
                    ])
                    ->visible(fn (Forms\Get $get) => in_array($get('tipo'), ['button', 'external_button'], true))
                    ->columns(3),
                    
                Forms\Components\Section::make('Codice Generato')
                    ->schema([
                        Forms\Components\Textarea::make('codice_generato')
                            ->label('Copia questo codice')
                            ->rows(6)
                            ->readOnly()
                            ->helperText('Copia e incolla questo codice nel sito del partner.'),
                            
                        Forms\Components\Placeholder::make('anteprima_rendering')
                            ->label('Anteprima Visiva (Live)')
                            ->content(function () {
                                $code = $this->previewCode ?? '';
                                if (empty($code)) {
                                    return 'Compila il form per vedere l\'anteprima';
                                }
                                return new \Illuminate\Support\HtmlString(
                                    '<div style="border:1px dashed #ccc; padding:15px; background:#f9f9f9; border-radius:8px; display:flex; justify-content:center;">' . 
                                    $code . 
                                    '</div>'
                                );
                            }),
                    ]),
            ])
            ->statePath('data');
    }
    
    public function generaCodice($overrides = [])
    {
        // Ottieni lo stato corrente del form
        $data = $this->form->getState();
        
        // Sovrascrivi con i valori appena modificati
        foreach ($overrides as $key => $value) {
            $data[$key] = $value;
        }

        $partnerId = $data['partner_id'] ?? null;
        
        if (!$partnerId) {
            $this->data['codice_generato'] = 'Seleziona un partner per generare il codice.';
            $this->previewCode = '';
            return;
        }
        
        $locationId = $data['location_id'] ?? null;
        $tipo = $data['tipo'] ?? 'link';
        
        $params = ['partner' => $partnerId, 'iframe' => '1'];
        $externalParams = ['partner' => $partnerId];
        
        if ($locationId) {
            // Se c'è una location specifica, punta direttamente alla pagina location
            $baseUrl = route('location.show', ['id' => $locationId]);
        } else {
            // Altrimenti punta alla home
            $baseUrl = route('home');
        }

        $url = $baseUrl . '?' . http_build_query($params);
        $externalUrl = $baseUrl . '?' . http_build_query($externalParams);
        
        $code = '';
        
        if ($tipo === 'link') {
            $code = $url;
        } elseif ($tipo === 'button' || $tipo === 'external_button') {
            $testo = trim($data['testo_pulsante'] ?? 'Prenota Ora') ?: 'Prenota Ora';
            $bg = $data['colore_sfondo'] ?? '#16A34A';
            $color = $data['colore_testo'] ?? '#ffffff';
            $style = 'display:inline-block; padding:12px 24px; background-color:' . e($bg) . '; color:' . e($color) . '; text-decoration:none; border-radius:30px; font-weight:bold; font-family:sans-serif;';

            if ($tipo === 'external_button') {
                $code = '<a href="' . e($externalUrl) . '" target="_blank" rel="noopener nofollow" style="' . $style . '">' . e($testo) . '</a>';
            } else {
                $code = '<a href="#iframenew" style="' . $style . '">' . e($testo) . '</a>';
            }
        } elseif ($tipo === 'iframe') {
            $code = <<<HTML
<iframe id="iframenew" src="{$url}" width="100%" height="800" frameborder="0" style="border:none; width:100%; min-height:800px; border-radius:12px;"></iframe>
HTML;
        }
        
        // Aggiorna lo stato del campo codice_generato
        $this->data['codice_generato'] = $code;
        
        // Aggiorna la proprietà di anteprima (importante per bypassare limiti del form state)
        $this->previewCode = $code;
        
        // Forza l'aggiornamento del form
        $this->form->fill($this->data);
    }
}
