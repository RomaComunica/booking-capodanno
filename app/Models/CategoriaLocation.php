<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CategoriaLocation extends Model
{
    protected $table = 'categorie_location';
    protected $primaryKey = 'idCatLocation';
    public $timestamps = false;
    protected $guarded = [];
}
