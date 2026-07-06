<?php
/**
 * Re-import tipologia_ingresso with the EXACT schema from the dump
 */
$db = new PDO('sqlite:database/test.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA foreign_keys=OFF');

// Drop and recreate with the EXACT dump schema (converted to SQLite)
$db->exec('DROP TABLE IF EXISTS "tipologia_ingresso"');
$db->exec('CREATE TABLE "tipologia_ingresso" (
    "idIngressoCont" INTEGER NOT NULL,
    "idIngresso" INTEGER NOT NULL,
    "idLocation" INTEGER NOT NULL DEFAULT 0,
    "nomeIngresso" TEXT NOT NULL DEFAULT \'\',
    "prezzoIngresso" REAL NOT NULL DEFAULT 0,
    "tipoProvvigione" INTEGER NOT NULL DEFAULT 0,
    "valoreProvvigione" REAL NOT NULL DEFAULT 0,
    "tipoQuantitaBonus" INTEGER NOT NULL DEFAULT 0,
    "quantitaBonus" INTEGER NOT NULL DEFAULT 0,
    "tipoProvvigioneBonus" INTEGER NOT NULL DEFAULT 0,
    "valoreProvvigioneBonus" REAL NOT NULL DEFAULT 0,
    "tipoProvvigionePartner" INTEGER NOT NULL DEFAULT 0,
    "valoreProvvigionePartner" REAL NOT NULL DEFAULT 0,
    "tipoQuantitaBonusPartner" INTEGER NOT NULL DEFAULT 0,
    "quantitaBonusPartner" INTEGER NOT NULL DEFAULT 0,
    "valoreProvvigioneBonusPartner" REAL NOT NULL DEFAULT 0,
    "giacenzaBiglietti" INTEGER NOT NULL DEFAULT 0,
    "ingressoAttivo" INTEGER NOT NULL DEFAULT 0,
    "tipoBigliettoIngresso" INTEGER NOT NULL DEFAULT 0,
    "orarioIngresso" TEXT NOT NULL DEFAULT \'00:00\',
    "giorniPrimaIngresso" INTEGER NOT NULL DEFAULT 1,
    "dataIngresso" TEXT DEFAULT NULL,
    "ingressoSenzaData" INTEGER NOT NULL DEFAULT 0,
    "etichettaIngresso" TEXT NOT NULL DEFAULT \'\',
    "opzioneRimborso" INTEGER NOT NULL DEFAULT 0,
    "rimborsoValorePersona" REAL DEFAULT NULL,
    "rimborsoDataScadenza" TEXT DEFAULT NULL,
    "rimborsoTesto" TEXT DEFAULT NULL,
    "ordine" INTEGER DEFAULT 0,
    "created_at" TEXT DEFAULT NULL,
    "updated_at" TEXT DEFAULT NULL,
    PRIMARY KEY ("idIngressoCont")
)');

echo "Table recreated.\n";

// Parse and insert
$lines = file(__DIR__ . '/capodanno20230203.sql', FILE_IGNORE_NEW_LINES);
$collecting = false;
$currentSql = '';
$insertCount = 0;
$errorCount = 0;

$db->beginTransaction();

foreach ($lines as $line) {
    $trimmed = trim($line);
    
    if (strpos($trimmed, 'INSERT INTO `tipologia_ingresso`') !== false) {
        $collecting = true;
        $currentSql = str_replace('`', '"', $trimmed);
        $currentSql = str_replace("\\'", "''", $currentSql);
        $currentSql = str_replace("'0000-00-00'", "NULL", $currentSql);
        $currentSql = str_replace("'0000-00-00 00:00:00'", "NULL", $currentSql);
        
        if (substr($trimmed, -1) === ';') {
            try {
                $n = $db->exec($currentSql);
                $insertCount += max(0, (int)$n);
            } catch (Exception $e) {
                $errorCount++;
                echo "ERR: " . $e->getMessage() . "\n";
            }
            $collecting = false;
            $currentSql = '';
        }
        continue;
    }
    
    if ($collecting) {
        $part = str_replace('`', '"', $line);
        $part = str_replace("\\'", "''", $part);
        $part = str_replace("'0000-00-00'", "NULL", $part);
        $part = str_replace("'0000-00-00 00:00:00'", "NULL", $part);
        $currentSql .= "\n" . $part;
        
        if (substr($trimmed, -1) === ';') {
            try {
                $n = $db->exec($currentSql);
                $insertCount += max(0, (int)$n);
            } catch (Exception $e) {
                $errorCount++;
                echo "ERR: " . $e->getMessage() . "\n";
            }
            $collecting = false;
            $currentSql = '';
        }
    }
}

$db->commit();

$count = $db->query('SELECT COUNT(*) FROM tipologia_ingresso')->fetchColumn();
echo "Inserted: $insertCount rows\n";
echo "Errors: $errorCount\n";
echo "Final count: $count\n";
