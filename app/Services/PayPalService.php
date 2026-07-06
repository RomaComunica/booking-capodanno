<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    private $apiUsername;
    private $apiPassword;
    private $apiSignature;
    private $apiEndpoint;
    private $redirectUrl;
    private $version = '204.0';

    public function __construct()
    {
        $mode = config('paypal.mode', 'sandbox');
        $config = config("paypal.{$mode}");

        $this->apiUsername = $config['username'];
        $this->apiPassword = $config['password'];
        $this->apiSignature = $config['signature'];
        $this->apiEndpoint = $config['endpoint'];
        $this->redirectUrl = $config['redirect_url'];
    }

    /**
     * SetExpressCheckout - Initialize PayPal payment
     */
    public function setExpressCheckout($amount, $returnUrl, $cancelUrl, $items = [])
    {
        $params = [
            'METHOD' => 'SetExpressCheckout',
            'VERSION' => $this->version,
            'USER' => $this->apiUsername,
            'PWD' => $this->apiPassword,
            'SIGNATURE' => $this->apiSignature,
            'PAYMENTREQUEST_0_AMT' => number_format($amount, 2, '.', ''),
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
            'RETURNURL' => $returnUrl,
            'CANCELURL' => $cancelUrl,
            'NOSHIPPING' => '1', // No shipping address required
            'ALLOWNOTE' => '0',
            'LANDINGPAGE' => 'Billing',
            'SOLUTIONTYPE' => 'Sole',
        ];

        // Add line items
        if (!empty($items)) {
            foreach ($items as $index => $item) {
                $params["L_PAYMENTREQUEST_0_NAME{$index}"] = $item['name'];
                $params["L_PAYMENTREQUEST_0_AMT{$index}"] = number_format($item['price'], 2, '.', '');
                $params["L_PAYMENTREQUEST_0_QTY{$index}"] = $item['quantity'];
            }
        }

        $response = $this->makeApiCall($params);

        if ($response['ACK'] === 'Success' || $response['ACK'] === 'SuccessWithWarning') {
            return [
                'success' => true,
                'token' => $response['TOKEN'],
                'redirect_url' => $this->redirectUrl . '?cmd=_express-checkout&token=' . $response['TOKEN'],
            ];
        }

        Log::error('PayPal SetExpressCheckout failed', $response);

        return [
            'success' => false,
            'error' => $response['L_LONGMESSAGE0'] ?? 'Unknown error',
        ];
    }

    /**
     * GetExpressCheckoutDetails - Get payer details
     */
    public function getExpressCheckoutDetails($token)
    {
        $params = [
            'METHOD' => 'GetExpressCheckoutDetails',
            'VERSION' => $this->version,
            'USER' => $this->apiUsername,
            'PWD' => $this->apiPassword,
            'SIGNATURE' => $this->apiSignature,
            'TOKEN' => $token,
        ];

        $response = $this->makeApiCall($params);

        if ($response['ACK'] === 'Success' || $response['ACK'] === 'SuccessWithWarning') {
            return [
                'success' => true,
                'payer_id' => $response['PAYERID'] ?? null,
                'email' => $response['EMAIL'] ?? null,
                'first_name' => $response['FIRSTNAME'] ?? null,
                'last_name' => $response['LASTNAME'] ?? null,
                'amount' => $response['PAYMENTREQUEST_0_AMT'] ?? null,
            ];
        }

        Log::error('PayPal GetExpressCheckoutDetails failed', $response);

        return [
            'success' => false,
            'error' => $response['L_LONGMESSAGE0'] ?? 'Unknown error',
        ];
    }

    /**
     * DoExpressCheckoutPayment - Complete the payment
     */
    public function doExpressCheckoutPayment($token, $payerId, $amount)
    {
        $params = [
            'METHOD' => 'DoExpressCheckoutPayment',
            'VERSION' => $this->version,
            'USER' => $this->apiUsername,
            'PWD' => $this->apiPassword,
            'SIGNATURE' => $this->apiSignature,
            'TOKEN' => $token,
            'PAYERID' => $payerId,
            'PAYMENTREQUEST_0_AMT' => number_format($amount, 2, '.', ''),
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
        ];

        $response = $this->makeApiCall($params);

        if ($response['ACK'] === 'Success' || $response['ACK'] === 'SuccessWithWarning') {
            return [
                'success' => true,
                'transaction_id' => $response['PAYMENTINFO_0_TRANSACTIONID'] ?? null,
                'amount' => $response['PAYMENTINFO_0_AMT'] ?? null,
                'status' => $response['PAYMENTINFO_0_PAYMENTSTATUS'] ?? null,
            ];
        }

        Log::error('PayPal DoExpressCheckoutPayment failed', $response);

        return [
            'success' => false,
            'error' => $response['L_LONGMESSAGE0'] ?? 'Unknown error',
        ];
    }

    /**
     * Make API call to PayPal
     */
    private function makeApiCall($params)
    {
        try {
            $request = Http::asForm();
            if (config('app.env') === 'local') {
                $request = $request->withoutVerifying();
            }
            $response = $request->post($this->apiEndpoint, $params);

            if ($response->failed()) {
                Log::error('PayPal API HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['ACK' => 'Failure', 'L_LONGMESSAGE0' => 'HTTP request failed'];
            }

            // Parse NVP response
            parse_str($response->body(), $result);

            Log::info('PayPal API call', [
                'method' => $params['METHOD'],
                'ack' => $result['ACK'] ?? 'Unknown',
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('PayPal API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['ACK' => 'Failure', 'L_LONGMESSAGE0' => $e->getMessage()];
        }
    }
}
