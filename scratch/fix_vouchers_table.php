<?php

try {
    $db = new PDO('sqlite:database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Checking vouchers table structure ===\n\n";

    // Get current columns
    $result = $db->query("PRAGMA table_info(vouchers)");
    $columns = [];
    foreach ($result as $row) {
        $columns[] = $row['name'];
        echo "✓ {$row['name']} ({$row['type']})\n";
    }

    echo "\n=== Columns used in OrderController::createVoucher ===\n\n";

    $usedColumns = [
        'numeroVoucher',
        'numeroOrdineVoucher',
        'idLocationVoucher',
        'dataVoucher',
        'nomeVoucher',
        'cognomeVoucher',
        'telefonoVoucher',
        'emailVoucher',
    ];

    $missingColumns = [];
    foreach ($usedColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ $col\n";
        } else {
            echo "❌ MISSING: $col\n";
            $missingColumns[] = $col;
        }
    }

    if (!empty($missingColumns)) {
        echo "\n=== Adding missing columns ===\n\n";
        
        $columnTypes = [
            'numeroVoucher' => 'INTEGER',
            'numeroOrdineVoucher' => 'INTEGER',
            'idLocationVoucher' => 'INTEGER',
            'dataVoucher' => 'DATETIME',
            'nomeVoucher' => 'VARCHAR(255)',
            'cognomeVoucher' => 'VARCHAR(255)',
            'telefonoVoucher' => 'VARCHAR(50)',
            'emailVoucher' => 'VARCHAR(255)',
        ];
        
        foreach ($missingColumns as $col) {
            $type = $columnTypes[$col] ?? 'VARCHAR(255)';
            try {
                $sql = "ALTER TABLE vouchers ADD COLUMN $col $type";
                $db->exec($sql);
                echo "✅ Added: $col ($type)\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'duplicate column') === false) {
                    echo "⚠️  Error adding $col: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "\n✅ All columns added!\n";
    } else {
        echo "\n✅ All required columns exist!\n";
    }

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
