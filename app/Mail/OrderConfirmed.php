<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Ordine;

class OrderConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public Ordine $ordine;

    public function __construct(Ordine $ordine, ?string $voucherAbsolutePath = null)
    {
        $this->ordine = $ordine;
        $this->subject('Conferma ordine n. '.$ordine->numeroOrdine);
    }

    public function build()
    {
        return $this->view('emails.order_confirmed', [
            'ordine' => $this->ordine,
        ]);
    }
}
