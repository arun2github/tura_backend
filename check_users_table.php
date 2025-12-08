<?php

require_once 'vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Database connected successfully!\n\n";
    
    // Check if users table exists
    $tablesQuery = "SHOW TABLES LIKE 'users'";
    $stmt = $pdo->query($tablesQuery);
    $usersTable = $stmt->fetch();
    
    if ($usersTable) {
        echo "📋 Users table exists. Structure:\n";
        $structQuery = "DESCRIBE users";
        $stmt = $pdo->query($structQuery);
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($structure as $column) {
            echo "  - {$column['Field']} ({$column['Type']}) NULL: {$column['Null']} KEY: {$column['Key']}\n";
        }
    } else {
        echo "❌ Users table does not exist!\n";
    }
    
    // Check all tables containing 'user'
    echo "\n📋 All tables containing 'user':\n";
    $userTablesQuery = "SHOW TABLES LIKE '%user%'";
    $stmt = $pdo->query($userTablesQuery);
    $userTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($userTables as $table) {
        echo "  - $table\n";
    }
    
    // Check migrations table to see what has been run
    echo "\n📋 Checking migrations:\n";
    $migrationsQuery = "SHOW TABLES LIKE 'migrations'";
    $stmt = $pdo->query($migrationsQuery);
    $migrationsTable = $stmt->fetch();
    
    if ($migrationsTable) {
        $migrationsDataQuery = "SELECT migration, batch FROM migrations ORDER BY id DESC LIMIT 10";
        $stmt = $pdo->query($migrationsDataQuery);
        $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($migrations as $migration) {
            echo "  - {$migration['migration']} (batch: {$migration['batch']})\n";
        }
    } else {
        echo "❌ Migrations table does not exist!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}