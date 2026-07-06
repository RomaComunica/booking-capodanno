<?php
use App\Models\Ordine;
use App\Models\Voucher;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$o = Ordine::find(2);
if ($o) {
    echo "Order 2 Exists.\n";
    $count = $o->vouchers()->count();
    echo "Vouchers count: $count\n";
    $v = $o->vouchers()->first();
    if ($v) {
        echo "First Voucher ID: " . $v->numeroVoucher . "\n";
    } else {
        echo "No voucher found via relationship.\n";
    }
} else {
    echo "Order 2 Not Found.\n";
}
