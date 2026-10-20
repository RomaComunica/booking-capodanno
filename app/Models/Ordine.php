<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Ordine extends Model
{
    protected $table = 'ordini';
    protected $primaryKey = 'numeroOrdine';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->numeroOrdine) {
                $maxNum = static::max('numeroOrdine') ?? 0;
                $model->numeroOrdine = $maxNum + 1;
            }
            if (!$model->dataOrdine) {
                $model->dataOrdine = now();
            }
        });

        static::saving(function ($model) {
            $notNullFields = ['noteOrdine', 'emailClienteOrdine', 'nomeClienteOrdine', 'cognomeClienteOrdine', 'telefonoClienteOrdine'];
            foreach ($notNullFields as $field) {
                if (is_null($model->$field)) {
                    $model->$field = '';
                }
            }
        });

        static::created(function ($model) {
            // Auto-create voucher for manual admin orders if none exists
            if (!\App\Models\Voucher::where('numeroOrdineVoucher', $model->numeroOrdine)->exists()) {
                $maxVoucher = \App\Models\Voucher::max('numeroVoucher') ?? 100;
                $numVoucher = $maxVoucher + 1;
                $codiceVoucher = 'VCH-' . strtoupper(substr(md5($numVoucher . time()), 0, 10));
                
                \App\Models\Voucher::create([
                    'numeroVoucher' => $numVoucher,
                    'codiceVoucher' => $codiceVoucher,
                    'numeroOrdineVoucher' => $model->numeroOrdine,
                    'idLocationVoucher' => $model->idLocationOrdine,
                    'dataVoucher' => now(),
                    'nomeVoucher' => $model->nomeClienteOrdine,
                    'cognomeVoucher' => $model->cognomeClienteOrdine,
                    'telefonoVoucher' => $model->telefonoClienteOrdine,
                    'emailVoucher' => $model->emailClienteOrdine,
                ]);
            }
        });

        static::updated(function ($model) {
            // Invia email automatica se lo stato passa a 2 (Completato)
            if ($model->isDirty('statoOrdine') && $model->statoOrdine == 2) {
                self::sendAutoEmail($model);
            }
        });
    }

    public static function sendAutoEmail($model)
    {
        try {
            $model->load(['righe', 'location', 'partner']);
            $voucher = \App\Models\Voucher::where('numeroOrdineVoucher', $model->numeroOrdine)->first();
            
            if ($voucher) {
                $email = $model->emailCliente ?? $model->emailClienteOrdine ?? null;
                if ($email) {
                    \Illuminate\Support\Facades\Mail::to($email)
                        ->send(new \App\Mail\OrderConfirmation($model, $voucher));
                    \Illuminate\Support\Facades\Log::info('Auto-sent voucher email for order #' . $model->numeroOrdine . ' to ' . $email);
                } else {
                    \Illuminate\Support\Facades\Log::warning('No email found to auto-send voucher for order #' . $model->numeroOrdine);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('No voucher found to auto-send email for order #' . $model->numeroOrdine);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Auto-send email failed: ' . $e->getMessage());
        }
    }

    public function righe() {
        return $this->hasMany(RigaOrdine::class, 'numeroOrdine', 'numeroOrdine');
    }
    public function vouchers() {
        return $this->hasMany(Voucher::class, 'numeroOrdineVoucher', 'numeroOrdine');
    }
    public function location() {
        return $this->belongsTo(Location::class, 'idLocationOrdine', 'idLocation');
    }
    public function partner() {
        return $this->belongsTo(Partner::class, 'idPartnerOrdine', 'idPartner');
    }
    public function tipoPagamento() {
        return $this->belongsTo(TipoPagamento::class, 'tipoPagamentoOrdine', 'idTipoPagamento');
    }
    public function tipoSpedizione() {
        return $this->belongsTo(TipoSpedizione::class, 'tipoSpedizioneOrdine', 'idSpedizione');
    }
}
