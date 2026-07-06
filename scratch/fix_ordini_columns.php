<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Checking ordini table structure ===\n\n";

    // Get current columns
    $result = $db->query("PRAGMA table_info(ordini)");
    $columns = [];
    foreach ($result as $row) {
        $columns[] = $row['name'];
        echo "✓ {$row['name']} ({$row['type']})\n";
    }

    echo "\n=== Checking for missing columns ===\n\n";

    $requiredColumns = [
        'tipoPagamentoOrdine' => 'INTEGER',
        'tipoSpedizioneOrdine' => 'INTEGER',
    ];

    $missingColumns = [];
    foreach ($requiredColumns as $column => $type) {
        if (!in_array($column, $columns)) {
            $missingColumns[$column] = $type;
            echo "❌ Missing: $column ($type)\n";
        } else {
            echo "✅ Exists: $column\n";
        }
    }

    if (!empty($missingColumns)) {
        echo "\n=== Adding missing columns ===\n\n";
        
        foreach ($missingColumns as $column => $type) {
            $sql = "ALTER TABLE ordini ADD COLUMN $column $type";
            $db->exec($sql);
            echo "✅ Added: $column ($type)\n";
        }
        
        echo "\n✅ All columns added successfully!\n";
    } else {
        echo "\n✅ All required columns exist!\n";
    }

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
