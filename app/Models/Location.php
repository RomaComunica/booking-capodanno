<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'location';
    protected $primaryKey = 'idLocation';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'attivaLocation' => 'boolean',
    ];

    public const SUGGESTED_CITIES = [
        'Roma',
        'Milano',
        'Firenze',
        'Napoli',
        'Torino',
        'Bologna',
        'Venezia',
        'Verona',
        'Rimini',
        'Genova',
        'Bari',
        'Palermo',
        'Catania',
        'Padova',
        'Brescia',
        'Bergamo',
        'Perugia',
        'Pisa',
        'Siena',
    ];

    /**
     * Restituisce l'elenco delle città presenti a database (chiave => valore) per i filtri.
     */
    public static function getAvailableCities(): array
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('location', 'cittaLocation')) {
                return ['Roma' => 'Roma'];
            }

            $cities = static::query()
                ->whereNotNull('cittaLocation')
                ->where('cittaLocation', '!=', '')
                ->select('cittaLocation')
                ->distinct()
                ->orderBy('cittaLocation')
                ->pluck('cittaLocation')
                ->map(fn ($c) => ucwords(strtolower(trim((string) $c))))
                ->filter()
                ->unique()
                ->values();

            $options = ['Roma' => 'Roma'];
            foreach ($cities as $city) {
                $options[$city] = $city;
            }
            return $options;
        } catch (\Throwable $e) {
            return ['Roma' => 'Roma'];
        }
    }

    /**
     * Restituisce i suggerimenti per il datalist del campo Città (città già a DB + principali città italiane).
     */
    public static function getCitySuggestions(): array
    {
        return array_values(array_unique(array_merge(
            array_values(static::getAvailableCities()),
            self::SUGGESTED_CITIES
        )));
    }

    public array $pendingPagamentiStato = [];

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        if ($this->exists && $this->idLocation) {
            $map = $this->getPagamentiStatoMap();
            foreach ([1, 2, 3, 4, 5] as $idPag) {
                $attributes['pagamento_' . $idPag] = $map[$idPag] ?? true;
            }
        }
        return $attributes;
    }

    public function getPagamentiStatoMap(): array
    {
        $defaults = [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
            5 => true,
        ];

        if (!$this->idLocation) {
            return $defaults;
        }

        try {
            $rows = \Illuminate\Support\Facades\DB::table('cross_pagamenti_location')
                ->where('idLocationPagamento', $this->idLocation)
                ->whereIn('idPagamento', [1, 2, 3, 4, 5])
                ->get(['idPagamento', 'pagamentoLocationAttivo']);

            foreach ($rows as $row) {
                $defaults[(int) $row->idPagamento] = ((int) $row->pagamentoLocationAttivo === 1);
            }
        } catch (\Throwable $e) {
            // Fallback to defaults
        }

        return $defaults;
    }

    public function isPagamentoAttivo(int $idPagamento): bool
    {
        $map = $this->getPagamentiStatoMap();
        return $map[$idPagamento] ?? true;
    }

    public function syncPagamentiLocation(array $states): void
    {
        if (!$this->idLocation) {
            return;
        }

        try {
            foreach ($states as $idPagamento => $isActive) {
                $idPagamento = (int) $idPagamento;
                if (!in_array($idPagamento, [1, 2, 3, 4, 5], true)) {
                    continue;
                }
                $attivo = $isActive ? 1 : 0;

                $exists = \Illuminate\Support\Facades\DB::table('cross_pagamenti_location')
                    ->where('idLocationPagamento', $this->idLocation)
                    ->where('idPagamento', $idPagamento)
                    ->exists();

                if ($exists) {
                    \Illuminate\Support\Facades\DB::table('cross_pagamenti_location')
                        ->where('idLocationPagamento', $this->idLocation)
                        ->where('idPagamento', $idPagamento)
                        ->update(['pagamentoLocationAttivo' => $attivo]);
                } else {
                    \Illuminate\Support\Facades\DB::table('cross_pagamenti_location')
                        ->insert([
                            'idLocationPagamento' => $this->idLocation,
                            'idPagamento' => $idPagamento,
                            'pagamentoLocationAttivo' => $attivo,
                        ]);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Errore syncPagamentiLocation: ' . $e->getMessage());
        }
    }

    public function ingressi() {
        return $this->hasMany(TipologiaIngresso::class, 'idLocation', 'idLocation');
    }

    public function categorie() {
        return $this->belongsToMany(CategoriaLocation::class, 'cross_location_categorie', 'idLocation', 'idCatLocation');
    }

    public function tipologie() {
        return $this->belongsToMany(TipologiaLocation::class, 'cross_location_tipologie', 'idLocation', 'idTipLocation');
    }

    public function pagamentiAbilitati() {
        return $this->belongsToMany(TipoPagamento::class, 'cross_pagamenti_location', 'idLocationPagamento', 'idPagamento')
            ->withPivot('pagamentoLocationAttivo');
    }

    public function spedizioniAbilitate() {
        return $this->belongsToMany(TipoSpedizione::class, 'cross_spedizioni_location', 'idLocationSpedizione', 'idSpedizione')
            ->withPivot('spedizioneLocationAttiva');
    }

    /**
     * Partners associati a questa location tramite cross_partner_ingressi
     */
    public function partners() {
        return $this->belongsToMany(Partner::class, 'cross_partner_ingressi', 'idlocation', 'idPartnerIngressi')
            ->distinct();
    }

    protected static function booted()
    {
        static::saving(function ($location) {
            foreach ([1, 2, 3, 4, 5] as $idPag) {
                $key = 'pagamento_' . $idPag;
                if (array_key_exists($key, $location->attributes)) {
                    $location->pendingPagamentiStato[$idPag] = (bool) $location->attributes[$key];
                    unset($location->attributes[$key]);
                }
            }

            try {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('location', 'ibanLocation')) {
                    \Illuminate\Support\Facades\Schema::table('location', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('ibanLocation', 50)->nullable()->default('');
                        $table->string('intestatarioLocation', 255)->nullable()->default('');
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('location', 'cittaLocation')) {
                    \Illuminate\Support\Facades\Schema::table('location', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('cittaLocation', 100)->nullable()->default('Roma')->after('indirizzoLocation');
                    });
                }
            } catch (\Throwable $e) {}

            $citta = trim((string) ($location->cittaLocation ?? ''));
            $location->cittaLocation = $citta !== '' ? ucwords(strtolower($citta)) : 'Roma';
        });

        static::saved(function ($location) {
            if (!empty($location->pendingPagamentiStato)) {
                $location->syncPagamentiLocation($location->pendingPagamentiStato);
                $location->pendingPagamentiStato = [];
            } elseif ($location->wasRecentlyCreated) {
                $location->syncPagamentiLocation([
                    1 => (bool) \App\Models\Setting::getVal('paypal_enabled', true),
                    2 => (bool) \App\Models\Setting::getVal('bonifico_enabled', true),
                    3 => (bool) \App\Models\Setting::getVal('contanti_enabled', true),
                    4 => (bool) \App\Models\Setting::getVal('postepay_enabled', true),
                    5 => (bool) \App\Models\Setting::getVal('nexi_enabled', true),
                ]);
            }

            if ($location->immagineLocation) {
                $filename = basename($location->immagineLocation);
                
                $sources = [
                    storage_path('app/public/' . $location->immagineLocation),
                    storage_path('app/public/locations/' . $filename),
                    storage_path('app/private/locations/' . $filename),
                    storage_path('app/locations/' . $filename),
                ];

                foreach ($sources as $source) {
                    if (file_exists($source) && !is_dir($source)) {
                        // 1. Ensure public/storage/locations exists and copy
                        $targetStorageDir = public_path('storage/locations');
                        if (!is_dir($targetStorageDir)) {
                            @mkdir($targetStorageDir, 0755, true);
                        }
                        @copy($source, public_path('storage/locations/' . $filename));

                        // 2. Also ensure public/img/locations exists and copy
                        $targetImgDir = public_path('img/locations');
                        if (!is_dir($targetImgDir)) {
                            @mkdir($targetImgDir, 0755, true);
                        }
                        @copy($source, public_path('img/locations/' . $filename));

                        break;
                    }
                }
            }
        });
    }

    public function getImageUrlAttribute()
    {
        if (empty($this->immagineLocation)) {
            // Check for image named by idLocation (e.g. loghi/5.webp, 5.jpg)
            $extensions = ['webp', 'jpg', 'jpeg', 'png'];
            foreach ($extensions as $ext) {
                if (file_exists(public_path("loghi/{$this->idLocation}.{$ext}"))) {
                    return asset("loghi/{$this->idLocation}.{$ext}");
                }
                if (file_exists(public_path("img/locations/{$this->idLocation}.{$ext}"))) {
                    return asset("img/locations/{$this->idLocation}.{$ext}");
                }
            }
            return null;
        }

        $img = $this->immagineLocation;

        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }

        $filename = basename($img);

        // 1. In public/img/locations/
        if (file_exists(public_path('img/locations/' . $filename))) {
            return asset('img/locations/' . $filename);
        }

        // 2. In public/storage/locations/
        if (file_exists(public_path('storage/locations/' . $filename))) {
            return asset('storage/locations/' . $filename);
        }

        // 3. Directly in public/
        if (file_exists(public_path($img))) {
            return asset($img);
        }

        // 4. In storage/app/public/
        if (file_exists(storage_path('app/public/' . $img)) || file_exists(storage_path('app/public/locations/' . $filename))) {
            return asset('storage/locations/' . $filename);
        }

        // 5. In storage/app/private/
        if (file_exists(storage_path('app/private/' . $img)) || file_exists(storage_path('app/private/locations/' . $filename))) {
            return asset('storage/locations/' . $filename);
        }

        // Fallback default
        return asset('storage/' . ltrim($img, '/'));
    }

    public function getImagePathAttribute()
    {
        if (empty($this->immagineLocation)) {
            $extensions = ['webp', 'jpg', 'jpeg', 'png'];
            foreach ($extensions as $ext) {
                if (file_exists(public_path("loghi/{$this->idLocation}.{$ext}"))) {
                    return public_path("loghi/{$this->idLocation}.{$ext}");
                }
                if (file_exists(public_path("img/locations/{$this->idLocation}.{$ext}"))) {
                    return public_path("img/locations/{$this->idLocation}.{$ext}");
                }
            }
            return null;
        }

        $img = $this->immagineLocation;
        $filename = basename($img);

        if (file_exists(public_path('img/locations/' . $filename))) {
            return public_path('img/locations/' . $filename);
        }
        if (file_exists(public_path('storage/locations/' . $filename))) {
            return public_path('storage/locations/' . $filename);
        }
        if (file_exists(public_path($img))) {
            return public_path($img);
        }
        if (file_exists(storage_path('app/public/' . $img))) {
            return storage_path('app/public/' . $img);
        }
        if (file_exists(storage_path('app/public/locations/' . $filename))) {
            return storage_path('app/public/locations/' . $filename);
        }
        if (file_exists(storage_path('app/private/' . $img))) {
            return storage_path('app/private/' . $img);
        }
        if (file_exists(storage_path('app/private/locations/' . $filename))) {
            return storage_path('app/private/locations/' . $filename);
        }

        return null;
    }
}
