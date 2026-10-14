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

    public function getTipologiaAttribute()
    {
        return $this->tipologiaIngresso
            ?? TipologiaIngresso::where('idIngressoCont', $this->idIngressoOrdine)->first()
            ?? TipologiaIngresso::where('idIngresso', $this->idIngressoOrdine)->first()
            ?? TipologiaIngresso::where('nomeIngresso', $this->nomeIngressoOrdine)->first();
    }

    public function getDataVisualizzataAttribute()
    {
        $raw = $this->attributes['dataIngressoOrdine'] ?? null;
        if (!empty($raw)) {
            try {
                return \Carbon\Carbon::parse($raw)->format('d/m/Y');
            } catch (\Throwable $e) {}
        }
        
        $tip = $this->tipologia;
        $tRaw = $tip ? ($tip->getAttributes()['dataIngresso'] ?? $tip->dataIngresso) : null;
        if (!empty($tRaw)) {
            try {
                return \Carbon\Carbon::parse($tRaw)->format('d/m/Y');
            } catch (\Throwable $e) {}
        }
        
        return null;
    }

    public function getOrarioVisualizzatoAttribute()
    {
        $raw = $this->attributes['orarioIngressoOrdine'] ?? null;
        if (!empty($raw)) {
            $clean = trim((string)$raw);
            if (preg_match('/(\d{1,2}:\d{2})/', $clean, $m)) {
                return strlen($m[1]) === 4 ? '0' . $m[1] : $m[1];
            }
        }
        
        $tip = $this->tipologia;
        $tRaw = $tip ? ($tip->getAttributes()['orarioIngresso'] ?? $tip->orarioIngresso) : null;
        if (!empty($tRaw)) {
            if ($tRaw instanceof \DateTimeInterface) {
                return $tRaw->format('H:i');
            }
            $clean = trim((string)$tRaw);
            if (preg_match('/(\d{1,2}:\d{2})/', $clean, $m)) {
                return strlen($m[1]) === 4 ? '0' . $m[1] : $m[1];
            }
        }
        
        return null;
    }

    public function partner() {
        return $this->belongsTo(Partner::class, 'idPartnerOrdine', 'idPartner');
    }
}
