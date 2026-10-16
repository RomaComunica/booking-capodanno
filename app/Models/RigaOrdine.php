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
     * Bridge legacy column names (`prezzoIngressoordine`, `quantitaIngressoordine`, `nomeIngressoordine`)
     * with the camelCase names (`prezzoIngressoOrdine`, `quantitaIngressoOrdine`, `nomeIngressoOrdine`)
     * used in Filament forms and Blade views.
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        $attributes['quantitaIngressoOrdine'] = $this->quantitaIngressoOrdine;
        $attributes['prezzoIngressoOrdine'] = $this->prezzoIngressoOrdine;
        $attributes['nomeIngressoOrdine'] = $this->nomeIngressoOrdine;

        return $attributes;
    }

    public function getPrezzoIngressoOrdineAttribute($value)
    {
        $raw = $value ?? ($this->attributes['prezzoIngressoOrdine'] ?? ($this->attributes['prezzoIngressoordine'] ?? null));
        if ($raw !== null && (float) $raw > 0) {
            return (float) $raw;
        }

        // Fallback: compute unit price from totaleRiga / quantita if available
        $totale = (float) ($this->attributes['totaleRiga'] ?? ($this->attributes['totaleriga'] ?? 0));
        $qty = max(1, (int) ($this->attributes['quantitaIngressoOrdine'] ?? ($this->attributes['quantitaIngressoordine'] ?? 1)));
        if ($totale > 0) {
            $opzRimborso = (int) ($this->attributes['opzioneRimborso'] ?? 0);
            $rimborsoVal = $opzRimborso ? (float) ($this->attributes['rimborsoValore'] ?? 0) : 0.0;
            $unit = round(($totale / $qty) - $rimborsoVal, 2);
            return $unit > 0 ? $unit : round($totale / $qty, 2);
        }

        return (float) ($raw ?? 0);
    }

    public function setPrezzoIngressoOrdineAttribute($value)
    {
        if (array_key_exists('prezzoIngressoordine', $this->attributes)) {
            $this->attributes['prezzoIngressoordine'] = $value;
            unset($this->attributes['prezzoIngressoOrdine']);
        } else {
            $this->attributes['prezzoIngressoOrdine'] = $value;
        }
    }

    public function getQuantitaIngressoOrdineAttribute($value)
    {
        if ($value !== null && $value != 0) return (int) $value;
        return (int) ($this->attributes['quantitaIngressoordine'] ?? $this->attributes['quantitaIngressoOrdine'] ?? 1);
    }

    public function setQuantitaIngressoOrdineAttribute($value)
    {
        if (array_key_exists('quantitaIngressoordine', $this->attributes)) {
            $this->attributes['quantitaIngressoordine'] = $value;
            unset($this->attributes['quantitaIngressoOrdine']);
        } else {
            $this->attributes['quantitaIngressoOrdine'] = $value;
        }
    }

    public function getNomeIngressoOrdineAttribute($value)
    {
        if (!empty($value)) return $value;
        return $this->attributes['nomeIngressoordine'] ?? $this->attributes['nomeIngressoOrdine'] ?? '';
    }

    public function setNomeIngressoOrdineAttribute($value)
    {
        if (array_key_exists('nomeIngressoordine', $this->attributes)) {
            $this->attributes['nomeIngressoordine'] = $value;
            unset($this->attributes['nomeIngressoOrdine']);
        } else {
            $this->attributes['nomeIngressoOrdine'] = $value;
        }
    }

    public function ordine() {
        return $this->belongsTo(Ordine::class, 'numeroOrdine', 'numeroOrdine');
    }
    public function tipologiaIngresso() {
        return $this->belongsTo(TipologiaIngresso::class, 'idIngressoOrdine', 'idIngressoCont');
    }

    public function getTipologiaAttribute()
    {
        if ($this->relationLoaded('tipologiaIngresso') && $this->getRelation('tipologiaIngresso')) {
            return $this->getRelation('tipologiaIngresso');
        }

        $locationId = $this->ordine?->idLocationOrdine;
        if (!$locationId && !empty($this->numeroOrdine)) {
            $locationId = Ordine::where('numeroOrdine', $this->numeroOrdine)->value('idLocationOrdine');
        }

        $nome = trim((string)$this->nomeIngressoOrdine);

        // 1. PRIORITÀ ASSOLUTA: Match per Location e Nome Biglietto (es. "Sport City" + "Cena di Gala Halloween")
        if ($locationId && !empty($nome)) {
            $match = TipologiaIngresso::where('idLocation', $locationId)
                ->where('nomeIngresso', $nome)
                ->first();
            if ($match) return $match;

            $match = TipologiaIngresso::where('idLocation', $locationId)
                ->whereRaw('TRIM(LOWER(nomeIngresso)) = ?', [strtolower($nome)])
                ->first();
            if ($match) return $match;

            $match = TipologiaIngresso::where('idLocation', $locationId)
                ->where('nomeIngresso', 'LIKE', '%' . $nome . '%')
                ->first();
            if ($match) return $match;
        }

        // 2. Match per Location e idIngresso (l'indice biglietto della location)
        if ($locationId && !empty($this->idIngressoOrdine)) {
            $match = TipologiaIngresso::where('idLocation', $locationId)
                ->where('idIngresso', $this->idIngressoOrdine)
                ->first();
            if ($match) return $match;

            $match = TipologiaIngresso::where('idLocation', $locationId)
                ->where('idIngressoCont', $this->idIngressoOrdine)
                ->first();
            if ($match) return $match;
        }

        // 3. Match per Nome Biglietto (case-insensitive) anche senza location
        if (!empty($nome)) {
            $match = TipologiaIngresso::whereRaw('TRIM(LOWER(nomeIngresso)) = ?', [strtolower($nome)])->first();
            if ($match) return $match;

            $match = TipologiaIngresso::where('nomeIngresso', 'LIKE', '%' . $nome . '%')->first();
            if ($match) return $match;
        }

        // 4. Solo come ultimo fallback controlla idIngressoCont (verificando preferibilmente che non appartenga a location diversa se nota)
        if (!empty($this->idIngressoOrdine)) {
            $match = TipologiaIngresso::where('idIngressoCont', $this->idIngressoOrdine)->first();
            if ($match) {
                if (!$locationId || $match->idLocation == $locationId) {
                    return $match;
                }
            }
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
                $formatted = \Carbon\Carbon::parse($tRaw)->format('d/m/Y');
                if ($formatted) return $formatted;
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
            // Aggiorna anche la riga ordine nel DB per renderla permanente
            $rawDb = $this->attributes['orarioIngressoOrdine'] ?? null;
            if ($rawDb === '00:00' || empty($rawDb)) {
                $this->attributes['orarioIngressoOrdine'] = $tipTime;
                try {
                    \DB::table('righe_ordine')
                        ->where('idRigaOrdine', $this->idRigaOrdine)
                        ->update(['orarioIngressoOrdine' => $tipTime]);
                } catch (\Throwable $e) {}
            }
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
