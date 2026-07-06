<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntrataUscita extends Model
{
    protected $table = 'entrate_uscite';
    protected $primaryKey = 'idEntrataUscita';
    
    protected $fillable = [
        'dataOperazione',
        'tipoOperazione',
        'causale',
        'importo',
        'descrizione'
    ];
    
    protected $casts = [
        'dataOperazione' => 'date',
        'importo' => 'decimal:2'
    ];
}
