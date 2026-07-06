<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $rows = DB::table('tipo_pagamenti')->get();
    file_put_contents('payment_types.txt', print_r($rows->toArray(), true));
    echo "Done.";
} catch (\Exception $e) {
    file_put_contents('payment_types.txt', "Error: " . $e->getMessage());
    echo "Error.";
}
