<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TipoPagamento extends Model
{
    protected $table = 'tipo_pagamenti';
    protected $primaryKey = 'idTipoPagamento';
    public $timestamps = false;
    protected $guarded = [];
    
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'cross_pagamenti_location', 
            'idPagamento', 'idLocationPagamento')
            ->withPivot('pagamentoLocationAttivo', 'costoPagamento');
    }
}
