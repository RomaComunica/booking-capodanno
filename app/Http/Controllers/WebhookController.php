<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Payments\PayPalService;

class WebhookController extends Controller
{
    public function paypal(Request $request, PayPalService $paypal)
    {
        $payload = $request->all();
        $paypal->handleWebhook($payload);
        return response()->json(['ok' => true], Response::HTTP_OK);
    }
}
