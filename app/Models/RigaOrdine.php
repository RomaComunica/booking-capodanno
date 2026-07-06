<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RigaOrdine extends Model
{
    protected $table = 'righe_ordine';
    protected $primaryKey = 'idRigaOrdine';
    public $timestamps = false;
    protected $guarded = [];

    /**
     * Accessor: bridge legacy column name `prezzoIngressoordine` (lowercase o)
     * with the name used throughout the app `prezzoIngressoOrdine` (uppercase O)
     */
    public function getPrezzoIngressoOrdineAttribute($value)
    {
        if ($value !== null && $value != 0) return $value;
        return $this->attributes['prezzoIngressoordine'] ?? $this->attributes['prezzoIngressoOrdine'] ?? 0;
    }

    public function getQuantitaIngressoOrdineAttribute($value)
    {
        if ($value !== null && $value != 0) return $value;
        return $this->attributes['quantitaIngressoordine'] ?? $this->attributes['quantitaIngressoOrdine'] ?? 0;
    }

    public function getNomeIngressoOrdineAttribute($value)
    {
        if (!empty($value)) return $value;
        return $this->attributes['nomeIngressoordine'] ?? $this->attributes['nomeIngressoOrdine'] ?? '';
    }

    public function ordine() {
        return $this->belongsTo(Ordine::class, 'numeroOrdine', 'numeroOrdine');
    }
    public function tipologiaIngresso() {
        return $this->belongsTo(TipologiaIngresso::class, 'idIngressoOrdine', 'idIngressoCont');
    }
    public function partner() {
        return $this->belongsTo(Partner::class, 'idPartnerOrdine', 'idPartner');
    }
}
