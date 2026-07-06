<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $table = 'partner';
    protected $primaryKey = 'idPartner';
    public $timestamps = false;
    protected $guarded = [];

    public function ingressiAbilitati() {
        return $this->belongsToMany(TipologiaIngresso::class, 'cross_partner_ingressi', 'idPartnerIngressi', 'idTipologiaIngresso')
            ->withPivot(['quantitaIngressi','quantitaIngressiTerra','idLocation']);
    }

    /**
     * Locations associate a questo partner tramite cross_partner_ingressi
     */
    public function locations() {
        return $this->belongsToMany(Location::class, 'cross_partner_ingressi', 'idPartnerIngressi', 'idlocation')
            ->distinct();
    }


    public function ordini() {
        return $this->hasMany(Ordine::class, 'idPartnerOrdine', 'idPartner');
    }

    /**
     * Accessor: Virtual attribute for Filament FileUpload component.
     * Searches for an existing logo in the public folder.
     */
    public function getLogoDummyAttribute()
    {
        if (!$this->idPartner) return null;
        
        $basePath = public_path('partner/loghi/' . $this->idPartner);
        $extensions = ['jpg', 'png', 'webp'];
        
        foreach ($extensions as $ext) {
            if (file_exists($basePath . '.' . $ext)) {
                return $this->idPartner . '.' . $ext;
            }
        }
        
        return null;
    }

    /**
     * Mutator: Required by Filament to 'hydrate' the virtual attribute
     * upon saving, allowing the FileUpload component to finalize the upload.
     */
    public function setLogoDummyAttribute($value)
    {
        // Filament saves the file to disk based on its own config. 
        // We just need this method to exist so Eloquent doesn't throw a "column not found" error
        // since we didn't add 'logo_dummy' to the actual database schema.
    }
}
