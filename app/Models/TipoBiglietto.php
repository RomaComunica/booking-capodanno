<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TipoBiglietto extends Model
{
    protected $table = 'tipo_biglietto';
    protected $primaryKey = 'idBiglietto';
    public $timestamps = false;
    protected $guarded = [];
}
