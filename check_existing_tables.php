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
    
    // Check for existing forms-related tables
    $tablesQuery = "SHOW TABLES LIKE '%form%'";
    $stmt = $pdo->query($tablesQuery);
    $formTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Form-related tables:\n";
    foreach ($formTables as $table) {
        echo "  - $table\n";
    }
    
    // Check for application-related tables
    $tablesQuery = "SHOW TABLES LIKE '%application%'";
    $stmt = $pdo->query($tablesQuery);
    $appTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📝 Application-related tables:\n";
    foreach ($appTables as $table) {
        echo "  - $table\n";
    }
    
    // Check for registration-related tables
    $tablesQuery = "SHOW TABLES LIKE '%registration%'";
    $stmt = $pdo->query($tablesQuery);
    $regTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📄 Registration-related tables:\n";
    foreach ($regTables as $table) {
        echo "  - $table\n";
    }
    
    // Check all tables
    $tablesQuery = "SHOW TABLES";
    $stmt = $pdo->query($tablesQuery);
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📊 All tables in database:\n";
    foreach ($allTables as $table) {
        echo "  - $table\n";
    }
    
    // Check if there's a main forms table
    if (in_array('forms', $allTables)) {
        echo "\n📋 Checking forms table structure:\n";
        $structQuery = "DESCRIBE forms";
        $stmt = $pdo->query($structQuery);
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($structure as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check sample data
        echo "\n📋 Sample forms data:\n";
        $sampleQuery = "SELECT * FROM forms LIMIT 5";
        $stmt = $pdo->query($sampleQuery);
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sampleData as $row) {
            echo "  ID: {$row['id']}, Title: " . (isset($row['title']) ? $row['title'] : 'N/A') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}