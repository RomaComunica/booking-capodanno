<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);

echo "Table: ordini\n";
$cols = $pdo->query("PRAGMA table_info(ordini)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "- {$c['name']} ({$c['type']})\n";
}

echo "\nTable: location\n";
$cols = $pdo->query("PRAGMA table_info(location)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "- {$c['name']} ({$c['type']})\n";
}

echo "\nTesting Select on View:\n";
try {
    $stmt = $pdo->query("SELECT * FROM clienti_view LIMIT 1");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
