<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== Columns of 'righe_ordine' table ===\n";
print_r(Schema::getColumnListing('righe_ordine'));

echo "=== Columns of 'voucher' table ===\n";
print_r(Schema::getColumnListing('voucher'));
