<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'voucher';
    protected $primaryKey = 'numeroVoucher';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'utilizzato' => 'boolean',
        'data_utilizzo' => 'datetime',
    ];

    public function ordine() {
        return $this->belongsTo(Ordine::class, 'numeroOrdineVoucher', 'numeroOrdine');
    }
    public function location() {
        return $this->belongsTo(Location::class, 'idLocationVoucher', 'idLocation');
    }
}
