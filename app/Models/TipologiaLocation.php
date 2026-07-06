<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TipologiaLocation extends Model
{
    protected $table = 'tipologie_location';
    protected $primaryKey = 'idTipLocation';
    public $timestamps = false;
    protected $guarded = [];
}
