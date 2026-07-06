<?php
namespace App\Services\Payments;

use App\Models\Ordine;

interface PaymentProviderInterface
{
    public function createOrder(Ordine $ordine): string; // returns approval URL
    public function captureOrder(string $token): array;  // [numeroOrdine, status]
    public function handleWebhook(array $payload): void;
}
