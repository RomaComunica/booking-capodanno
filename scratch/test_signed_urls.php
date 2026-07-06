<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

echo "=== STARTING SIGNED URLS TEST ===\n\n";

try {
    $numeroOrdine = 999;
    $numeroVoucher = 999;

    // 1. Generate normal URL
    $normalUrl = route('voucher.show', ['numeroOrdine' => $numeroOrdine, 'numeroVoucher' => $numeroVoucher]);
    echo "Normal URL:  $normalUrl\n";

    // 2. Generate signed URL
    $signedUrl = URL::signedRoute('voucher.show', ['numeroOrdine' => $numeroOrdine, 'numeroVoucher' => $numeroVoucher]);
    echo "Signed URL:  $signedUrl\n\n";

    // 3. Test validation of normal URL (should fail)
    echo "Testing validation of normal URL...\n";
    $requestNormal = Request::create($normalUrl, 'GET');
    // Note: in Laravel request->hasValidSignature() requires the query parameter signature.
    $isValidNormal = $requestNormal->hasValidSignature();
    echo "Normal URL valid? " . ($isValidNormal ? "YES (FAILED SECURITY)" : "NO (SUCCESS)") . "\n\n";

    // 4. Test validation of signed URL (should succeed)
    echo "Testing validation of signed URL...\n";
    
    // We parse query parameters from the signed URL
    $parsedUrl = parse_url($signedUrl);
    parse_str($parsedUrl['query'] ?? '', $queryParams);
    
    $requestSigned = Request::create($signedUrl, 'GET', $queryParams);
    
    $isValidSigned = $requestSigned->hasValidSignature();
    echo "Signed URL valid? " . ($isValidSigned ? "YES (SUCCESS)" : "NO (FAILED)") . "\n\n";

    if (!$isValidNormal && $isValidSigned) {
        echo "=== [SUCCESS] SIGNED URL SECURITY SYSTEM WORKS PERFECTLY! ===\n";
    } else {
        echo "=== [FAILURE] SIGNED URL VERIFICATION FAILED ===\n";
    }

} catch (\Exception $e) {
    echo "=== ERROR OCCURRED ===\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
