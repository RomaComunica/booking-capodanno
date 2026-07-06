<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Removing Postepay Payment Method ===\n\n";

    // Delete Postepay from tipo_pagamenti
    $db->exec("DELETE FROM tipo_pagamenti WHERE idTipoPagamento = 4");
    echo "✓ Removed 'Ricarica Postepay'\n";

    // Delete Postepay associations from cross table
    $db->exec("DELETE FROM cross_pagamenti_location WHERE idPagamento = 4");
    echo "✓ Removed Postepay associations from locations\n";

    echo "\n=== Remaining Payment Methods ===\n\n";

    // Show remaining methods
    $result = $db->query("SELECT * FROM tipo_pagamenti ORDER BY idTipoPagamento");
    foreach ($result as $row) {
        echo "✓ ID {$row['idTipoPagamento']}: {$row['nomePagamento']}\n";
    }

    echo "\n✅ Payment methods updated!\n";
    echo "Remaining: PayPal, Bonifico, Contanti, Nexi\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
