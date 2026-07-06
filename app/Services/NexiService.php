<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NexiService
{
    private $alias;
    private $secretKey;
    private $gatewayUrl;
    private $currencyCode = '978'; // EUR

    public function __construct()
    {
        $mode = config('nexi.mode', 'test');
        $config = config("nexi.{$mode}");

        $this->alias = $config['alias'];
        $this->secretKey = $config['secret_key'];
        $this->gatewayUrl = $config['gateway_url'];
    }

    /**
     * Prepare payment data for Nexi redirect
     */
    public function preparePaymentData($amount, $codTrans, $returnUrl, $cancelUrl)
    {
        // Convert amount to cents (Nexi requires amount in cents)
        $importo = (int) ($amount * 100);

        $params = [
            'alias' => $this->alias,
            'importo' => $importo,
            'divisa' => $this->currencyCode,
            'codTrans' => $codTrans,
            'url' => $returnUrl,
            'url_back' => $cancelUrl,
        ];

        // Calculate MAC
        $params['mac'] = $this->calculateMac($params);

        Log::info('Nexi payment prepared', [
            'codTrans' => $codTrans,
            'importo' => $importo,
            'amount' => $amount,
        ]);

        return [
            'gateway_url' => $this->gatewayUrl,
            'params' => $params,
        ];
    }

    /**
     * Calculate MAC for payment request
     * MAC = SHA1(codTrans + divisa + importo + secretKey)
     */
    public function calculateMac($params)
    {
        $macString = 'codTrans=' . $params['codTrans'] .
                     'divisa=' . $params['divisa'] .
                     'importo=' . $params['importo'] .
                     $this->secretKey;

        $mac = sha1($macString);

        Log::debug('Nexi MAC calculated', [
            'mac_string' => 'codTrans=' . $params['codTrans'] . 'divisa=' . $params['divisa'] . 'importo=' . $params['importo'] . '[SECRET_KEY]',
            'mac' => $mac,
        ]);

        return $mac;
    }

    /**
     * Verify MAC from Nexi callback
     * MAC = SHA1(codTrans + esito + importo + divisa + data + orario + codAut + secretKey)
     */
    public function verifyCallbackMac($params)
    {
        $receivedMac = $params['mac'] ?? '';

        $macString = 'codTrans=' . ($params['codTrans'] ?? '') .
                     'esito=' . ($params['esito'] ?? '') .
                     'importo=' . ($params['importo'] ?? '') .
                     'divisa=' . ($params['divisa'] ?? '') .
                     'data=' . ($params['data'] ?? '') .
                     'orario=' . ($params['orario'] ?? '') .
                     'codAut=' . ($params['codAut'] ?? '') .
                     $this->secretKey;

        $calculatedMac = sha1($macString);

        $isValid = $calculatedMac === $receivedMac;

        Log::info('Nexi MAC verification', [
            'is_valid' => $isValid,
            'received_mac' => $receivedMac,
            'calculated_mac' => $calculatedMac,
        ]);

        return $isValid;
    }

    /**
     * Parse Nexi callback response
     */
    public function parseCallback($params)
    {
        // Verify MAC first
        if (!$this->verifyCallbackMac($params)) {
            return [
                'success' => false,
                'error' => 'MAC verification failed',
            ];
        }

        $esito = $params['esito'] ?? '';

        // Esito codes:
        // OK = Payment successful
        // KO = Payment failed
        // ANNULLO = Payment cancelled by user

        if ($esito === 'OK') {
            return [
                'success' => true,
                'transaction_id' => $params['codAut'] ?? null,
                'cod_trans' => $params['codTrans'] ?? null,
                'amount' => isset($params['importo']) ? ((int) $params['importo']) / 100 : null,
                'date' => $params['data'] ?? null,
                'time' => $params['orario'] ?? null,
                'message' => $params['messaggio'] ?? 'Pagamento completato',
            ];
        }

        return [
            'success' => false,
            'error' => $params['messaggio'] ?? 'Pagamento fallito',
            'esito' => $esito,
        ];
    }

    /**
     * Generate unique transaction code
     */
    public function generateTransactionCode()
    {
        return 'ORD-' . time() . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}
