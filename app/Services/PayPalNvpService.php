<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalNvpService
{
    private $username;
    private $password;
    private $signature;
    private $endpoint;
    private $mode;

    public function __construct()
    {
        $this->username = config('services.paypal.nvp_username');
        $this->password = config('services.paypal.nvp_password');
        $this->signature = config('services.paypal.nvp_signature');
        $this->mode = config('services.paypal.mode', 'sandbox');
        
        $this->endpoint = $this->mode === 'live'
            ? 'https://api-3t.paypal.com/nvp'
            : 'https://api-3t.sandbox.paypal.com/nvp';
    }

    /**
     * Create PayPal Express Checkout payment
     */
    public function createPayment($ordine, $returnUrl, $cancelUrl)
    {
        $params = [
            'METHOD' => 'SetExpressCheckout',
            'VERSION' => '124.0',
            'USER' => $this->username,
            'PWD' => $this->password,
            'SIGNATURE' => $this->signature,
            'PAYMENTREQUEST_0_AMT' => number_format($ordine->totaleOrdine, 2, '.', ''),
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
            'PAYMENTREQUEST_0_INVNUM' => $ordine->idOrdine,
            'PAYMENTREQUEST_0_DESC' => 'Ordine #' . $ordine->idOrdine,
            'RETURNURL' => $returnUrl,
            'CANCELURL' => $cancelUrl,
            'NOSHIPPING' => '1',
            'ALLOWNOTE' => '0',
        ];

        $response = Http::asForm()->post($this->endpoint, $params);
        $result = $this->parseNvpResponse($response->body());

        if ($result['ACK'] === 'Success' || $result['ACK'] === 'SuccessWithWarning') {
            $redirectUrl = $this->mode === 'live'
                ? 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . $result['TOKEN']
                : 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . $result['TOKEN'];

            return [
                'success' => true,
                'token' => $result['TOKEN'],
                'redirect_url' => $redirectUrl,
            ];
        }

        Log::error('PayPal SetExpressCheckout failed', $result);
        return [
            'success' => false,
            'error' => $result['L_LONGMESSAGE0'] ?? 'Unknown error',
        ];
    }

    /**
     * Execute PayPal payment after user approval
     */
    public function executePayment($token, $payerId, $amount)
    {
        $params = [
            'METHOD' => 'DoExpressCheckoutPayment',
            'VERSION' => '124.0',
            'USER' => $this->username,
            'PWD' => $this->password,
            'SIGNATURE' => $this->signature,
            'TOKEN' => $token,
            'PAYERID' => $payerId,
            'PAYMENTREQUEST_0_AMT' => number_format($amount, 2, '.', ''),
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
        ];

        $response = Http::asForm()->post($this->endpoint, $params);
        $result = $this->parseNvpResponse($response->body());

        if ($result['ACK'] === 'Success' || $result['ACK'] === 'SuccessWithWarning') {
            return [
                'success' => true,
                'transaction_id' => $result['PAYMENTINFO_0_TRANSACTIONID'],
                'status' => $result['PAYMENTINFO_0_PAYMENTSTATUS'],
            ];
        }

        Log::error('PayPal DoExpressCheckoutPayment failed', $result);
        return [
            'success' => false,
            'error' => $result['L_LONGMESSAGE0'] ?? 'Unknown error',
        ];
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails($token)
    {
        $params = [
            'METHOD' => 'GetExpressCheckoutDetails',
            'VERSION' => '124.0',
            'USER' => $this->username,
            'PWD' => $this->password,
            'SIGNATURE' => $this->signature,
            'TOKEN' => $token,
        ];

        $response = Http::asForm()->post($this->endpoint, $params);
        $result = $this->parseNvpResponse($response->body());

        if ($result['ACK'] === 'Success' || $result['ACK'] === 'SuccessWithWarning') {
            return [
                'success' => true,
                'payer_id' => $result['PAYERID'] ?? null,
                'email' => $result['EMAIL'] ?? null,
                'amount' => $result['PAYMENTREQUEST_0_AMT'] ?? null,
            ];
        }

        return ['success' => false];
    }

    /**
     * Parse NVP response string into array
     */
    private function parseNvpResponse($nvpString)
    {
        $result = [];
        parse_str($nvpString, $result);
        return $result;
    }
}
