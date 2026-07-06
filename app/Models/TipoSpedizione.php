<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TipoSpedizione extends Model
{
    protected $table = 'tipo_spedizione';
    protected $primaryKey = 'idSpedizione';
    public $timestamps = false;
    protected $guarded = [];
    
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'cross_spedizioni_location',
            'idSpedizione', 'idLocationSpedizione')
            ->withPivot('spedizioneLocationAttiva', 'costoSpedizione');
    }
}
