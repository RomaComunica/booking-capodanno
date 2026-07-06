<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    if ($table === 'sqlite_sequence') continue;
    $count = $db->query("SELECT count(*) FROM \"$table\"")->fetchColumn();
    echo "$table: $count rows\n";
}
