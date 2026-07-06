<?php
$pdo = new PDO('sqlite:database/database.sqlite');
echo "Table: tipo_spedizione\n";
$cols = $pdo->query("PRAGMA table_info(tipo_spedizione)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "- {$c['name']} ({$c['type']})\n";
}
