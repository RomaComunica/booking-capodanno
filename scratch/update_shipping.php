<?php
try {
    $dbPath = __DIR__ . '/database/database_v2.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Try to add the column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE tipo_spedizione ADD COLUMN testoAggiuntivo TEXT");
    } catch (Exception $e) {
        // Column might already exist, ignore
    }

    $stmt1 = $pdo->prepare("UPDATE tipo_spedizione SET descrizioneSpedizione=?, testoAggiuntivo=? WHERE idSpedizione=1");
    $stmt1->execute([
        'Ritiro via Email (Voucher)', 
        'Riceverai un\'e-mail con il tuo codice P.R.N. e tutte le istruzioni del tuo acquisto on line da presentare all\'ingresso.'
    ]);
    
    $stmt2 = $pdo->prepare("UPDATE tipo_spedizione SET descrizioneSpedizione=?, testoAggiuntivo=? WHERE idSpedizione=2");
    $stmt2->execute([
        'Ritiro a mano', 
        'Ritiro a mano su appuntamento presso la nostra sede.'
    ]);

    echo "Shipping methods updated successfully.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
