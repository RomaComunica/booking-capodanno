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
        static::saving(function ($model) {
            $notNullFields = ['noteOrdine', 'emailClienteOrdine', 'nomeClienteOrdine', 'cognomeClienteOrdine', 'telefonoClienteOrdine'];
            foreach ($notNullFields as $field) {
                if (is_null($model->$field)) {
                    $model->$field = '';
                }
            }
        });

        static::updated(function ($model) {
            // Invia email automatica se lo stato passa a 2 (Completato)
            if ($model->isDirty('statoOrdine') && $model->statoOrdine == 2) {
                self::sendAutoEmail($model);
            }
        });
    }

    protected static function sendAutoEmail($model)
    {
        try {
            $model->loadMissing(['righe', 'location', 'partner']);
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
