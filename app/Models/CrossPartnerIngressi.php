<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrossPartnerIngressi extends Model
{
    protected $table = 'cross_partner_ingressi';
    public $timestamps = false;
    protected $guarded = [];

    /**
     * Relazione con Partner
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'idPartnerIngressi', 'idPartner');
    }

    /**
     * Relazione con Location
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'idlocation', 'idLocation');
    }

    /**
     * Relazione con Tipologia Ingresso
     */
    public function tipologiaIngresso()
    {
        return $this->belongsTo(TipologiaIngresso::class, 'idTipologiaIngresso', 'idIngresso');
    }
}
