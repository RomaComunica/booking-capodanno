<?php
/**
 * Recreate admin user with correct password hash
 */

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("❌ Database non trovato\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connesso al database\n\n";
    
    // Delete existing admin user
    $pdo->exec("DELETE FROM users WHERE email = 'admin@booking.local'");
    echo "🗑️  Utente admin esistente rimosso\n";
    
    // Create new password hash
    $password = password_hash('password', PASSWORD_BCRYPT);
    echo "🔐 Password hash generato\n";
    
    // Insert new admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, created_at, updated_at)
        VALUES (?, ?, ?, datetime('now'), datetime('now'))
    ");
    
    $stmt->execute(['Admin', 'admin@booking.local', $password]);
    
    echo "✅ Nuovo utente admin creato\n\n";
    
    // Verify
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE email = 'admin@booking.local'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Verifica utente:\n";
        echo "   ID: {$user['id']}\n";
        echo "   Nome: {$user['name']}\n";
        echo "   Email: {$user['email']}\n\n";
        
        echo "🔑 Credenziali:\n";
        echo "   Email: admin@booking.local\n";
        echo "   Password: password\n\n";
        
        echo "✅ Prova ora il login su http://localhost:8001/admin\n";
    } else {
        echo "❌ Errore: Utente non trovato dopo creazione\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
