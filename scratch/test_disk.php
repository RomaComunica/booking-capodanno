<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$disk = Illuminate\Support\Facades\Storage::disk('partner_loghi');
echo "Disk URL for 5.webp: " . $disk->url('5.webp') . "\n";
echo "Disk Path for 5.webp: " . $disk->path('5.webp') . "\n";

$p = App\Models\Partner::find(5);
if ($p) {
    echo "Model Attribute logo_dummy: " . $p->logo_dummy . "\n";
} else {
    echo "Partner 5 not found.\n";
}
