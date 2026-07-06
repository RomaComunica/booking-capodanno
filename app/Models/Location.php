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

    public function getImageUrlAttribute()
    {
        if ($this->immagineLocation) {
            if (\Storage::disk('public')->exists($this->immagineLocation)) {
                return asset('storage/' . $this->immagineLocation);
            }
            if (file_exists(public_path('img/locations/' . $this->immagineLocation))) {
                return asset('img/locations/' . $this->immagineLocation);
            }
        }
        return null;
    }

    public function getImagePathAttribute()
    {
        if ($this->immagineLocation) {
            if (\Storage::disk('public')->exists($this->immagineLocation)) {
                return \Storage::disk('public')->path($this->immagineLocation);
            }
            if (file_exists(public_path('img/locations/' . $this->immagineLocation))) {
                return public_path('img/locations/' . $this->immagineLocation);
            }
        }
        return null;
    }

}
