<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class NexiService
{
    private $alias;
    private $secretKey;
    private $gatewayUrl;
    private $currencyCode = 'EUR';

    public function __construct()
    {
        // Use DB setting if present, otherwise default to production
        $mode = Setting::getVal('nexi_mode', config('nexi.mode', 'production'));
        if (!in_array($mode, ['production', 'test'], true)) {
            $mode = 'production';
        }

        $config = config("nexi.{$mode}", []);

        $defaultGateway = $mode === 'production'
            ? 'https://ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet'
            : 'https://int-ecommerce.nexi.it/ecomm/ecomm/DispatcherServlet';

        $dbAlias = Setting::getVal('nexi_alias', '');
        $dbSecret = Setting::getVal('nexi_secret_key', '');

        $cfgAlias = $config['alias'] ?? '';
        $cfgSecret = $config['secret_key'] ?? '';

        $this->alias = $this->resolveCredential($dbAlias, $cfgAlias);
        $this->secretKey = $this->resolveCredential($dbSecret, $cfgSecret);
        $this->gatewayUrl = $config['gateway_url'] ?? $defaultGateway;
        $this->currencyCode = 'EUR';
    }

    private function resolveCredential(?string $dbVal, ?string $cfgVal): string
    {
        $dbVal = trim((string) $dbVal);
        if ($dbVal !== '' && !str_starts_with($dbVal, 'your_')) {
            return $dbVal;
        }

        $cfgVal = trim((string) $cfgVal);
        if ($cfgVal !== '' && !str_starts_with($cfgVal, 'your_')) {
            return $cfgVal;
        }

        return '';
    }

    /**
     * Prepare payment data for Nexi redirect
     */
    public function preparePaymentData($amount, $codTrans, $returnUrl, $cancelUrl, array $customer = [])
    {
        // Convert amount to cents (Nexi requires integer amount in cents)
        $importo = (int) round(((float) $amount) * 100);

        $params = [
            'alias' => $this->alias,
            'importo' => $importo,
            'divisa' => $this->currencyCode,
            'codTrans' => $codTrans,
            'url' => $returnUrl,
            'url_back' => $cancelUrl,
        ];

        // Calculate MAC (codTrans=...divisa=...importo=... + secretKey)
        $params['mac'] = $this->calculateMac($params);

        // Optional customer info supported by Nexi XPay Simple Payment (as in legacy nexigateway.asp)
        if (!empty($customer['nome'])) {
            $params['nome'] = $customer['nome'];
        }
        if (!empty($customer['cognome'])) {
            $params['cognome'] = $customer['cognome'];
        }
        if (!empty($customer['email'])) {
            $params['mail'] = $customer['email'];
        }

        Log::info('Nexi payment prepared', [
            'gateway_url' => $this->gatewayUrl,
            'alias' => $this->alias,
            'codTrans' => $codTrans,
            'importo' => $importo,
            'divisa' => $this->currencyCode,
            'amount' => $amount,
        ]);

        return [
            'gateway_url' => $this->gatewayUrl,
            'params' => $params,
        ];
    }

    /**
     * Calculate MAC for payment request
     * MAC = lowercase SHA1(codTrans=...divisa=...importo=... + secretKey)
     */
    public function calculateMac($params)
    {
        $macString = 'codTrans=' . $params['codTrans'] .
                     'divisa=' . $params['divisa'] .
                     'importo=' . $params['importo'] .
                     $this->secretKey;

        $mac = strtolower(sha1($macString));

        Log::debug('Nexi MAC calculated', [
            'mac_string' => 'codTrans=' . $params['codTrans'] . 'divisa=' . $params['divisa'] . 'importo=' . $params['importo'] . '[SECRET_KEY]',
            'mac' => $mac,
        ]);

        return $mac;
    }

    /**
     * Verify MAC from Nexi callback
     * MAC = SHA1(codTrans=...esito=...importo=...divisa=...data=...orario=...codAut=... + secretKey)
     */
    public function verifyCallbackMac($params)
    {
        $receivedMac = strtolower(trim((string) ($params['mac'] ?? '')));

        $macString = 'codTrans=' . ($params['codTrans'] ?? '') .
                     'esito=' . ($params['esito'] ?? '') .
                     'importo=' . ($params['importo'] ?? '') .
                     'divisa=' . ($params['divisa'] ?? '') .
                     'data=' . ($params['data'] ?? '') .
                     'orario=' . ($params['orario'] ?? '') .
                     'codAut=' . ($params['codAut'] ?? '') .
                     $this->secretKey;

        $calculatedMac = strtolower(sha1($macString));

        $isValid = ($receivedMac !== '' && hash_equals($calculatedMac, $receivedMac));

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
        $esito = strtoupper(trim((string) ($params['esito'] ?? '')));

        // If user cancelled or KO without MAC, handle gracefully
        if ($esito === 'ANNULLO') {
            return [
                'success' => false,
                'cancelled' => true,
                'error' => 'Pagamento annullato dall\'utente',
                'esito' => $esito,
            ];
        }

        // Verify MAC first
        if (!$this->verifyCallbackMac($params)) {
            return [
                'success' => false,
                'error' => 'Verifica di sicurezza MAC non riuscita',
                'esito' => $esito,
            ];
        }

        if ($esito === 'OK') {
            return [
                'success' => true,
                'transaction_id' => $params['codAut'] ?? ($params['codTrans'] ?? null),
                'cod_trans' => $params['codTrans'] ?? null,
                'amount' => isset($params['importo']) ? ((int) $params['importo']) / 100 : null,
                'date' => $params['data'] ?? null,
                'time' => $params['orario'] ?? null,
                'message' => $params['messaggio'] ?? 'Pagamento completato',
            ];
        }

        return [
            'success' => false,
            'error' => $params['messaggio'] ?? 'Transazione rifiutata o non riuscita',
            'esito' => $esito,
        ];
    }

    /**
     * Generate unique transaction code (matching legacy ID_YYYYMMDDHHMMSSxx format, max 30 chars)
     */
    public function generateTransactionCode()
    {
        return 'ID_' . date('YmdHis') . random_int(10, 99);
    }
}

