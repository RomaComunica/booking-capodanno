<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComuneItaliano extends Model
{
    protected $table = 'comuni_italiani';
    protected $primaryKey = 'idComune';
    public $timestamps = false;
    protected $guarded = [];
}
