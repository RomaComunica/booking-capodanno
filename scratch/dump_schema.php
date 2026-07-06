<?php
try {
    $dbPath = __DIR__ . '/database/database.sqlite.bak';
    echo "Reading schema from $dbPath\n";
    $pdo = new PDO('sqlite:' . $dbPath);
    
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $sqlContent = "";
    foreach ($tables as $sql) {
        if ($sql) {
            $sqlContent .= $sql . ";\n\n";
        }
    }
    
    file_put_contents(__DIR__ . '/reconstruct_schema.sql', $sqlContent);
    echo "Schema dump saved to reconstruct_schema.sql\n";
    echo "Tables found: " . count($tables) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
