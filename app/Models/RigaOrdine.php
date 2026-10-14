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
        if ($this->tipologiaIngresso) {
            return $this->tipologiaIngresso;
        }

        if (!empty($this->idIngressoOrdine)) {
            $byCont = TipologiaIngresso::where('idIngressoCont', $this->idIngressoOrdine)->first();
            if ($byCont) return $byCont;

            $locationId = $this->ordine?->idLocationOrdine;
            if ($locationId) {
                $byIdAndLoc = TipologiaIngresso::where('idLocation', $locationId)
                    ->where('idIngresso', $this->idIngressoOrdine)
                    ->first();
                if ($byIdAndLoc) return $byIdAndLoc;
            }

            $byId = TipologiaIngresso::where('idIngresso', $this->idIngressoOrdine)->first();
            if ($byId) return $byId;
        }

        $locationId = $this->ordine?->idLocationOrdine;
        if ($locationId && !empty($this->nomeIngressoOrdine)) {
            $byNameAndLoc = TipologiaIngresso::where('idLocation', $locationId)
                ->where('nomeIngresso', $this->nomeIngressoOrdine)
                ->first();
            if ($byNameAndLoc) return $byNameAndLoc;
        }

        if (!empty($this->nomeIngressoOrdine)) {
            return TipologiaIngresso::where('nomeIngresso', $this->nomeIngressoOrdine)->first();
        }

        return null;
    }

    public function getDataVisualizzataAttribute()
    {
        // 1. Controlla prima la tipologia se ha una data impostata
        $tip = $this->tipologia;
        $tRaw = $tip ? ($tip->getAttributes()['dataIngresso'] ?? $tip->dataIngresso) : null;
        if (!empty($tRaw)) {
            try {
                return \Carbon\Carbon::parse($tRaw)->format('d/m/Y');
            } catch (\Throwable $e) {}
        }

        // 2. Altrimenti la data salvata nella riga d'ordine
        $raw = $this->attributes['dataIngressoOrdine'] ?? null;
        if (!empty($raw)) {
            try {
                return \Carbon\Carbon::parse($raw)->format('d/m/Y');
            } catch (\Throwable $e) {}
        }
        
        return null;
    }

    public function getOrarioVisualizzatoAttribute()
    {
        $extractTime = function ($val) {
            if (empty($val)) return null;
            if ($val instanceof \DateTimeInterface) {
                return $val->format('H:i');
            }
            $clean = trim((string)$val);
            if (preg_match('/(\d{1,2})[:.](\d{2})/', $clean, $m)) {
                return sprintf('%02d:%02d', $m[1], $m[2]);
            }
            return null;
        };

        // 1. Controlla prima la tipologia biglietto: se la tipologia ha un orario valido (es. 20:30), quello è il dato autorevole aggiornato dall'amministratore!
        $tip = $this->tipologia;
        $tRaw = $tip ? ($tip->getAttributes()['orarioIngresso'] ?? $tip->orarioIngresso) : null;
        $tipTime = $extractTime($tRaw);
        if (!empty($tipTime) && $tipTime !== '00:00') {
            return $tipTime;
        }

        // 2. Controlla la riga d'ordine se ha un orario valido diverso da 00:00
        $raw = $this->attributes['orarioIngressoOrdine'] ?? null;
        $orderLineTime = $extractTime($raw);
        if (!empty($orderLineTime) && $orderLineTime !== '00:00') {
            return $orderLineTime;
        }

        // 3. Se la tipologia ha esplicitamente 00:00
        if ($tipTime === '00:00') {
            return '00:00';
        }

        // 4. Se la riga ordine ha 00:00
        if ($orderLineTime === '00:00') {
            return '00:00';
        }

        return null;
    }

    public function partner() {
        return $this->belongsTo(Partner::class, 'idPartnerOrdine', 'idPartner');
    }
}
