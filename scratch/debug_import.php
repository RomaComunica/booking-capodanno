<?php
$db = new PDO('sqlite:database/test.sqlite');
$out = "";

$out .= "tipologia_ingresso count: " . $db->query("SELECT COUNT(*) FROM tipologia_ingresso")->fetchColumn() . "\n";
$out .= "Min idIngressoCont: " . $db->query("SELECT MIN(idIngressoCont) FROM tipologia_ingresso")->fetchColumn() . "\n";
$out .= "Max idIngressoCont: " . $db->query("SELECT MAX(idIngressoCont) FROM tipologia_ingresso")->fetchColumn() . "\n";

// Check if 558 exists
$out .= "id=558 exists: " . ($db->query("SELECT COUNT(*) FROM tipologia_ingresso WHERE idIngressoCont=558")->fetchColumn() ? "YES" : "NO") . "\n";

// Check the range around 558
$r = $db->query("SELECT idIngressoCont FROM tipologia_ingresso WHERE idIngressoCont BETWEEN 550 AND 575 ORDER BY idIngressoCont");
$out .= "IDs 550-575: ";
while ($row = $r->fetch()) $out .= $row[0] . " ";
$out .= "\n";

// How many unique idIngressoOrdine in righe_ordine have no match
$out .= "Unmatched IDs: " . $db->query("SELECT COUNT(DISTINCT r.idIngressoOrdine) FROM righe_ordine r WHERE NOT EXISTS (SELECT 1 FROM tipologia_ingresso t WHERE t.idIngressoCont=r.idIngressoOrdine)")->fetchColumn() . "\n";
$out .= "Total unique IDs: " . $db->query("SELECT COUNT(DISTINCT idIngressoOrdine) FROM righe_ordine")->fetchColumn() . "\n";

// Check the dump to see how many INSERT statements there were
$lines = file(__DIR__ . '/capodanno20230203.sql', FILE_IGNORE_NEW_LINES);
$insertCount = 0;
foreach ($lines as $line) {
    if (strpos($line, 'INSERT INTO `tipologia_ingresso`') !== false) {
        $insertCount++;
    }
}
$out .= "INSERT statements in dump: $insertCount\n";

file_put_contents('debug_output.txt', $out);
echo "Done\n";
