<?php
/**
 * Add missing cache table to SQLite database
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("❌ Database non trovato: $dbPath\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database SQLite\n\n";
    
    // Create cache table
    echo "📦 Creando tabella cache...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS cache (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL,
        expiration INTEGER NOT NULL
    )");
    echo "  ✅ Tabella cache creata\n\n";
    
    // Create cache_locks table
    echo "🔒 Creando tabella cache_locks...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS cache_locks (
        key TEXT PRIMARY KEY,
        owner TEXT NOT NULL,
        expiration INTEGER NOT NULL
    )");
    echo "  ✅ Tabella cache_locks creata\n\n";
    
    // Create sessions table
    echo "👤 Creando tabella sessions...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS sessions (
        id TEXT PRIMARY KEY,
        user_id INTEGER,
        ip_address TEXT,
        user_agent TEXT,
        payload TEXT NOT NULL,
        last_activity INTEGER NOT NULL
    )");
    echo "  ✅ Tabella sessions creata\n\n";
    
    // Create jobs table (for queue)
    echo "⚙️ Creando tabella jobs...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        queue TEXT NOT NULL,
        payload TEXT NOT NULL,
        attempts INTEGER NOT NULL,
        reserved_at INTEGER,
        available_at INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    )");
    echo "  ✅ Tabella jobs creata\n\n";
    
    // Create failed_jobs table
    echo "❌ Creando tabella failed_jobs...\n";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS failed_jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid TEXT NOT NULL UNIQUE,
        connection TEXT NOT NULL,
        queue TEXT NOT NULL,
        payload TEXT NOT NULL,
        exception TEXT NOT NULL,
        failed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "  ✅ Tabella failed_jobs creata\n\n";
    
    echo "🎉 Tutte le tabelle di sistema create!\n";
    echo "\n✅ Ora riprova il login su http://localhost:8001/admin\n";
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
