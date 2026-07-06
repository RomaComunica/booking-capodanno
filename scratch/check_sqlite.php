<?php
$db = new PDO('sqlite:database/test.sqlite');

$tables = [
    'tipo_biglietto',
    'tipo_spedizione',
    'voucher',
    'entrateuscite',
    'cross_location_categorie',
    'cross_location_tipologie',
    'cross_partner_ingressi'
];

foreach ($tables as $table) {
    echo "=== Table: $table ===\n";
    $stmt = $db->query("PRAGMA table_info($table)");
    if ($stmt) {
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo " - {$col['name']} ({$col['type']}) pk={$col['pk']}\n";
        }
    } else {
        echo "Table does not exist in SQLite.\n";
    }
}

