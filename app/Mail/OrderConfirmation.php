<?php

namespace App\Mail;

use App\Models\Ordine;
use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $voucher;
    public $orderLines;
    public $location;
    public $partner;
    public $additionalMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Ordine $order, Voucher $voucher, ?string $additionalMessage = null)
    {
        $this->order = $order;
        $this->voucher = $voucher;
        $this->orderLines = $order->righe;
        $this->location = $order->location;
        $this->partner = $order->partner;
        $this->additionalMessage = $additionalMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $bccs = [];
        
        // Aggiungi email della location in copia
        if ($this->location) {
            if (!empty($this->location->emailLocation)) {
                $bccs[] = $this->location->emailLocation;
            }
            if (!empty($this->location->emailRifLocation)) {
                $bccs[] = $this->location->emailRifLocation;
            }
        }
        
        // Aggiungi email del partner in copia
        if ($this->partner && !empty($this->partner->emailPartner)) {
            $bccs[] = $this->partner->emailPartner;
        }

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                \App\Models\Setting::getVal('email_notifiche', 'booking@romacomunica.it'), 
                \App\Models\Setting::getVal('nome_mittente', 'Booking Online')
            ),
            subject: 'Riepilogo ordine #' . $this->order->numeroOrdine,
            bcc: array_unique($bccs),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
