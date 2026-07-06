<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/database/database_v2.sqlite');
$stmt = $pdo->query('SELECT * FROM ordini WHERE numeroOrdine = 101');
$ordini = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Ordine:\n";
print_r($ordini);

$stmt2 = $pdo->query('SELECT * FROM righe_ordine WHERE numeroOrdine = 101');
$righe = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "Righe Ordine:\n";
print_r($righe);
