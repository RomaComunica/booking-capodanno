<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table';");
foreach ($tables as $table) {
    echo $table->name . "\n";
}
