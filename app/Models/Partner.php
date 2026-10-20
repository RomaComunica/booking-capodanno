<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $table = 'partner';
    protected $primaryKey = 'idPartner';
    public $timestamps = false;
    protected $guarded = [];

    protected $appends = ['logo_dummy'];

    protected ?string $pendingLogoValue = null;
    protected bool $logoExplicitlyModified = false;

    protected static function booted()
    {
        static::saved(function (Partner $partner) {
            if (!$partner->idPartner) {
                return;
            }

            $publicDir = public_path('partner/loghi');
            $storageDir = storage_path('app/public/partner/loghi');
            $publicStorageDir = public_path('storage/partner/loghi');

            foreach ([$publicDir, $storageDir, $publicStorageDir] as $dir) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
            }

            if ($partner->logoExplicitlyModified) {
                if (!empty($partner->pendingLogoValue)) {
                    $uploadedName = basename($partner->pendingLogoValue);
                    $ext = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION) ?: 'jpg');
                    $targetName = $partner->idPartner . '.' . $ext;

                    $sourceCandidates = [
                        $publicDir . DIRECTORY_SEPARATOR . $uploadedName,
                        $storageDir . DIRECTORY_SEPARATOR . $uploadedName,
                        storage_path('app/public/' . ltrim($partner->pendingLogoValue, '/')),
                    ];

                    $sourceFile = null;
                    foreach ($sourceCandidates as $candidate) {
                        if (file_exists($candidate) && !is_dir($candidate)) {
                            $sourceFile = $candidate;
                            break;
                        }
                    }

                    if ($sourceFile) {
                        $targetPublic = $publicDir . DIRECTORY_SEPARATOR . $targetName;
                        if (realpath($sourceFile) !== realpath($targetPublic)) {
                            @copy($sourceFile, $targetPublic);
                        }
                        @copy($targetPublic, $storageDir . DIRECTORY_SEPARATOR . $targetName);
                        @copy($targetPublic, $publicStorageDir . DIRECTORY_SEPARATOR . $targetName);

                        if (str_starts_with($uploadedName, 'temp_') && file_exists($sourceFile) && realpath($sourceFile) !== realpath($targetPublic)) {
                            @unlink($sourceFile);
                        }

                        // Remove older files for the same partner with different extensions
                        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'JPG', 'JPEG', 'PNG', 'WEBP', 'GIF'] as $otherExt) {
                            $candidateName = $partner->idPartner . '.' . $otherExt;
                            if ($candidateName !== $targetName) {
                                @unlink($publicDir . DIRECTORY_SEPARATOR . $candidateName);
                                @unlink($storageDir . DIRECTORY_SEPARATOR . $candidateName);
                                @unlink($publicStorageDir . DIRECTORY_SEPARATOR . $candidateName);
                            }
                        }
                    }
                } else {
                    // Logo was explicitly removed
                    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'JPG', 'JPEG', 'PNG', 'WEBP', 'GIF'] as $ext) {
                        $candidateName = $partner->idPartner . '.' . $ext;
                        @unlink($publicDir . DIRECTORY_SEPARATOR . $candidateName);
                        @unlink($storageDir . DIRECTORY_SEPARATOR . $candidateName);
                        @unlink($publicStorageDir . DIRECTORY_SEPARATOR . $candidateName);
                    }
                }
            }
        });
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        $attributes['logo_dummy'] = $this->logo_dummy;
        return $attributes;
    }

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
     * Searches for an existing logo in the public or storage folder.
     */
    public function getLogoDummyAttribute()
    {
        if (!$this->idPartner) {
            return null;
        }

        $publicDir = public_path('partner/loghi');
        $storageDir = storage_path('app/public/partner/loghi');
        $publicStorageDir = public_path('storage/partner/loghi');

        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }

        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'JPG', 'JPEG', 'PNG', 'WEBP', 'GIF'];
        $foundFiles = [];

        foreach ($extensions as $ext) {
            $fileName = $this->idPartner . '.' . $ext;
            $pubFile = $publicDir . DIRECTORY_SEPARATOR . $fileName;
            $storFile = $storageDir . DIRECTORY_SEPARATOR . $fileName;
            $pubStorFile = $publicStorageDir . DIRECTORY_SEPARATOR . $fileName;

            if (file_exists($pubFile) && !is_dir($pubFile)) {
                $foundFiles[$fileName] = @filemtime($pubFile) ?: 1;
            } elseif (file_exists($storFile) && !is_dir($storFile)) {
                @copy($storFile, $pubFile);
                $foundFiles[$fileName] = @filemtime($storFile) ?: 1;
            } elseif (file_exists($pubStorFile) && !is_dir($pubStorFile)) {
                @copy($pubStorFile, $pubFile);
                $foundFiles[$fileName] = @filemtime($pubStorFile) ?: 1;
            }
        }

        if (empty($foundFiles)) {
            return null;
        }

        arsort($foundFiles);
        return array_key_first($foundFiles);
    }

    /**
     * Public URL of the partner logo (supports all image formats + cache busting).
     */
    public function getLogoUrlAttribute(): ?string
    {
        $fileName = $this->logo_dummy;
        if (!$fileName) {
            return null;
        }

        $filePath = public_path('partner/loghi/' . $fileName);
        $version = file_exists($filePath) ? @filemtime($filePath) : null;

        return asset('partner/loghi/' . $fileName) . ($version ? '?v=' . $version : '');
    }

    /**
     * Mutator: Required by Filament to 'hydrate' the virtual attribute
     * upon saving, allowing the FileUpload component to finalize the upload.
     */
    public function setLogoDummyAttribute($value)
    {
        $this->logoExplicitlyModified = true;
        if (is_array($value)) {
            $this->pendingLogoValue = !empty($value) ? (string) reset($value) : null;
        } else {
            $this->pendingLogoValue = !empty($value) ? (string) $value : null;
        }
        unset($this->attributes['logo_dummy']);
    }

    /**
     * Rileva automaticamente la sigla provincia (es. MI, FI, NA, TO, VE) per i partner fuori Roma.
     * Restituisce null per i partner di Roma, così da evidenziare solo gli ordini fuori Roma.
     */
    public function getSiglaCittaAttribute(): ?string
    {
        $haystack = strtolower(trim(
            ($this->nomePartner ?? '') . ' ' .
            ($this->emailPartner ?? '') . ' ' .
            ($this->urlPartner ?? '')
        ));

        if ($haystack === '' || str_contains($haystack, 'roma')) {
            return null;
        }

        $cityMap = [
            'milano'    => 'MI',
            'firenze'   => 'FI',
            'napoli'    => 'NA',
            'torino'    => 'TO',
            'venezia'   => 'VE',
            'bologna'   => 'BO',
            'genova'    => 'GE',
            'bari'      => 'BA',
            'palermo'   => 'PA',
            'verona'    => 'VR',
            'catania'   => 'CT',
            'rimini'    => 'RN',
            'brescia'   => 'BS',
            'bergamo'   => 'BG',
            'padova'    => 'PD',
            'pisa'      => 'PI',
            'siena'     => 'SI',
            'perugia'   => 'PG',
            'lecce'     => 'LE',
            'cagliari'  => 'CA',
            'trieste'   => 'TS',
            'parma'     => 'PR',
            'modena'    => 'MO',
            'salerno'   => 'SA',
            'pescara'   => 'PE',
            'ancona'    => 'AN',
        ];

        foreach ($cityMap as $city => $sigla) {
            if (str_contains($haystack, $city)) {
                return $sigla;
            }
        }

        return null;
    }

    /**
     * Restituisce il badge HTML colorato per la sigla provincia fuori Roma.
     */
    public function getBadgeCittaHtmlAttribute(): ?string
    {
        $sigla = $this->sigla_citta;
        if (!$sigla) {
            return null;
        }

        $palette = [
            'FI' => ['bg' => '#7c3aed', 'text' => '#ffffff'], // Viola Firenze
            'MI' => ['bg' => '#2563eb', 'text' => '#ffffff'], // Blu Milano
            'NA' => ['bg' => '#0284c7', 'text' => '#ffffff'], // Azzurro Napoli
            'TO' => ['bg' => '#b45309', 'text' => '#ffffff'], // Ambra/Granata Torino
            'VE' => ['bg' => '#0d9488', 'text' => '#ffffff'], // Verde Laguna Venezia
            'BO' => ['bg' => '#dc2626', 'text' => '#ffffff'],
            'GE' => ['bg' => '#1d4ed8', 'text' => '#ffffff'],
            'BA' => ['bg' => '#e11d48', 'text' => '#ffffff'],
            'PA' => ['bg' => '#db2777', 'text' => '#ffffff'],
            'VR' => ['bg' => '#ca8a04', 'text' => '#ffffff'],
        ];

        $colors = $palette[$sigla] ?? ['bg' => '#4f46e5', 'text' => '#ffffff'];
        $title = htmlspecialchars($this->nomePartner ?? $sigla, ENT_QUOTES, 'UTF-8');

        return '<span title="Partner: ' . $title . '" style="display:inline-flex;align-items:center;justify-content:center;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:800;letter-spacing:0.05em;background-color:' . $colors['bg'] . ';color:' . $colors['text'] . ';box-shadow:0 1px 2px rgba(0,0,0,0.15);">' . $sigla . '</span>';
    }
}
