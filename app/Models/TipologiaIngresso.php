<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TipologiaIngresso extends Model
{
    protected $table = 'tipologia_ingresso';
    protected $primaryKey = 'idIngressoCont';
    public $timestamps = false;
    protected $guarded = [];

    protected $attributes = [
        'etichettaIngresso' => '',
        'rimborsoTesto' => '',
        'nomeIngresso' => '',
        'orarioIngresso' => '00:00',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            // Convert NULL to empty string for NOT NULL text columns
            $notNullFields = ['etichettaIngresso', 'rimborsoTesto', 'nomeIngresso'];
            foreach ($notNullFields as $field) {
                if (is_null($model->$field)) {
                    $model->$field = '';
                }
            }
            if (is_null($model->idIngresso)) {
                $model->idIngresso = 0;
            }
        });

        static::deleting(function ($model) {
            $model->partners()->detach();
        });
    }

    protected $casts = [
        'prezzoIngresso' => 'decimal:2',
        'valoreProvvigione' => 'decimal:2',
        'valoreProvvigioneBonus' => 'decimal:2',
        'valoreProvvigionePartner' => 'decimal:2',
        'valoreProvvigioneBonusPartner' => 'decimal:2',
        'rimborsoValorePersona' => 'decimal:2',
        'ingressoAttivo' => 'boolean',
        'ingressoSenzaData' => 'boolean',
        'opzioneRimborso' => 'boolean',
        'dataIngresso' => 'date',
        'rimborsoDataScadenza' => 'datetime',
        'ordine' => 'integer',
    ];

    public function setOrarioIngressoAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['orarioIngresso'] = null;
            return;
        }
        if ($value instanceof \DateTimeInterface) {
            $this->attributes['orarioIngresso'] = $value->format('H:i:s');
            return;
        }
        $clean = trim((string)$value);
        if (preg_match('/(\d{1,2})[:.](\d{2})/', $clean, $m)) {
            $this->attributes['orarioIngresso'] = sprintf('%02d:%02d:00', $m[1], $m[2]);
            return;
        }
        $this->attributes['orarioIngresso'] = $value;
    }

    public function getOrarioIngressoAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }
        $clean = trim((string)$value);
        if (preg_match('/(\d{1,2})[:.](\d{2})/', $clean, $m)) {
            return sprintf('%02d:%02d', $m[1], $m[2]);
        }
        return $value;
    }

    public function location() {
        return $this->belongsTo(Location::class, 'idLocation', 'idLocation');
    }

    public function tipoBiglietto() {
        return $this->belongsTo(TipoBiglietto::class, 'tipoBigliettoIngresso', 'idBiglietto');
    }

    public function partners() {
        return $this->belongsToMany(Partner::class, 'cross_partner_ingressi', 'idTipologiaIngresso', 'idPartnerIngressi')
            ->withPivot('quantitaIngressi', 'quantitaIngressiTerra', 'idLocation');
    }
}
