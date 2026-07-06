<?php
$db = new PDO('sqlite:database/test.sqlite');

echo "=== LOCATION COLS ===\n";
$r = $db->query("PRAGMA table_info(location)");
while($row = $r->fetch(PDO::FETCH_ASSOC)) {
    if(stripos($row['name'], 'email') !== false) {
        echo "{$row['name']}\n";
    }
}

echo "=== PARTNER COLS ===\n";
$r = $db->query("PRAGMA table_info(partner)");
while($row = $r->fetch(PDO::FETCH_ASSOC)) {
    if(stripos($row['name'], 'email') !== false) {
        echo "{$row['name']}\n";
    }
}
