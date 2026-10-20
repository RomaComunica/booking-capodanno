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
            try {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('location', 'ibanLocation')) {
                    \Illuminate\Support\Facades\Schema::table('location', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('ibanLocation', 50)->nullable()->default('');
                        $table->string('intestatarioLocation', 255)->nullable()->default('');
                    });
                }
            } catch (\Throwable $e) {}
        });

        static::saved(function ($location) {
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
