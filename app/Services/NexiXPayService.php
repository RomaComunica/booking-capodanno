<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NexiXPayService
{
    private $alias;
    private $secret;
    private $endpoint;
    private $mode;

    public function __construct()
    {
        $this->alias = config('services.nexi.alias');
        $this->secret = config('services.nexi.secret');
        $this->mode = config('services.nexi.mode', 'test');
        
        $this->endpoint = $this->mode === 'prod'
            ? 'https://ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet'
            : 'https://int-ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet';
    }

    /**
     * Generate payment form data with MAC signature
     */
    public function generatePaymentForm($ordine, $returnUrl, $callbackUrl)
    {
        $codTrans = 'ORD' . str_pad($ordine->idOrdine, 10, '0', STR_PAD_LEFT);
        $importo = number_format($ordine->totaleOrdine * 100, 0, '', ''); // Amount in cents
        $divisa = 'EUR';
        $timestamp = time() * 1000; // Milliseconds

        // Calculate MAC signature
        $macString = 'codTrans=' . $codTrans
            . 'divisa=' . $divisa
            . 'importo=' . $importo
            . $this->secret;
        
        $mac = hash('sha1', $macString);

        return [
            'action' => $this->endpoint,
            'fields' => [
                'alias' => $this->alias,
                'importo' => $importo,
                'divisa' => $divisa,
                'codTrans' => $codTrans,
                'url' => $returnUrl,
                'url_back' => $returnUrl,
                'urlpost' => $callbackUrl,
                'mac' => $mac,
                'languageId' => 'ITA',
                'descrizione' => 'Ordine #' . $ordine->idOrdine,
                'mail' => $ordine->emailCliente ?? '',
            ],
        ];
    }

    /**
     * Verify MAC signature from callback
     */
    public function verifyCallback($data)
    {
        if (!isset($data['esito']) || !isset($data['mac'])) {
            Log::error('Nexi callback missing required fields', $data);
            return false;
        }

        // Build MAC string for verification
        $macString = 'codTrans=' . ($data['codTrans'] ?? '')
            . 'esito=' . $data['esito']
            . 'importo=' . ($data['importo'] ?? '')
            . 'divisa=' . ($data['divisa'] ?? '')
            . 'data=' . ($data['data'] ?? '')
            . 'orario=' . ($data['orario'] ?? '')
            . 'codAut=' . ($data['codAut'] ?? '')
            . $this->secret;

        $calculatedMac = hash('sha1', $macString);

        if ($calculatedMac !== $data['mac']) {
            Log::error('Nexi MAC verification failed', [
                'expected' => $calculatedMac,
                'received' => $data['mac'],
            ]);
            return false;
        }

        return true;
    }

    /**
     * Parse payment result
     */
    public function parsePaymentResult($data)
    {
        if (!$this->verifyCallback($data)) {
            return [
                'success' => false,
                'error' => 'MAC verification failed',
            ];
        }

        // esito: OK = success, KO = failed, ANNULLO = cancelled
        $success = $data['esito'] === 'OK';

        return [
            'success' => $success,
            'transaction_id' => $data['codTrans'] ?? null,
            'auth_code' => $data['codAut'] ?? null,
            'amount' => isset($data['importo']) ? ($data['importo'] / 100) : null,
            'status' => $data['esito'] ?? null,
            'message' => $data['messaggio'] ?? null,
            'date' => $data['data'] ?? null,
            'time' => $data['orario'] ?? null,
        ];
    }
}
