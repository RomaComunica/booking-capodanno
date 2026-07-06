<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Removing GRA Shipping Methods ===\n\n";

    // Delete GRA shipping methods from tipo_spedizione
    $db->exec("DELETE FROM tipo_spedizione WHERE idSpedizione IN (2, 3)");
    echo "✓ Removed 'Consegna a domicilio dentro GRA'\n";
    echo "✓ Removed 'Consegna a domicilio fuori GRA'\n";

    // Delete GRA associations from cross table
    $db->exec("DELETE FROM cross_spedizioni_location WHERE idSpedizione IN (2, 3)");
    echo "✓ Removed GRA associations from locations\n";

    echo "\n=== Remaining Shipping Methods ===\n\n";

    // Show remaining methods
    $result = $db->query("SELECT * FROM tipo_spedizione ORDER BY idSpedizione");
    foreach ($result as $row) {
        echo "✓ ID {$row['idSpedizione']}: {$row['nomeSpedizione']}\n";
    }

    echo "\n=== Verifying Associations ===\n\n";

    // Count associations
    $count = $db->query("SELECT COUNT(*) as cnt FROM cross_spedizioni_location")->fetch();
    echo "Total location associations: {$count['cnt']}\n";

    echo "\n✅ Shipping methods updated successfully!\n";
    echo "Only 'Voucher via E-mail' and 'Ritiro in ufficio' remain.\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
