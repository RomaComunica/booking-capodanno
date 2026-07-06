<?php

use App\Services\PayPalService;

$service = new PayPalService();
$result = $service->setExpressCheckout(50.00, 'http://localhost/return', 'http://localhost/cancel', [
    ['name' => 'Ticket Test', 'price' => 50.00, 'quantity' => 1]
]);

var_dump($result);
