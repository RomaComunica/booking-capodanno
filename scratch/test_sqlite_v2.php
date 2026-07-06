<?php
try {
    $dbPath = __DIR__ . '/database/database_v2.sqlite';
    echo "Connecting to $dbPath\n";
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating table test...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, name TEXT)");
    echo "Table created.\n";
    
    $pdo->exec("INSERT INTO test (name) VALUES ('foo')");
    echo "Row inserted.\n";
    
    $stmt = $pdo->query("SELECT * FROM test");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
