<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clienti_view';
    
    // Essendo una vista, non abbiamo un ID autoincrementante
    public $incrementing = false;
    protected $primaryKey = 'email';
    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'nome',
        'cognome',
        'telefono',
        'numero_ordini',
        'totale_speso',
        'ultimo_ordine',
    ];

    protected $casts = [
        'totale_speso' => 'decimal:2',
        'ultimo_ordine' => 'datetime',
    ];
    
    // Relazione con gli ordini originali
    public function ordini()
    {
        return $this->hasMany(Ordine::class, 'emailClienteOrdine', 'email');
    }
}
