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
    
    // Check forms table structure
    echo "📋 Forms table structure:\n";
    $structQuery = "DESCRIBE forms";
    $stmt = $pdo->query($structQuery);
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($structure as $column) {
        echo "  - {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']} {$column['Default']}\n";
    }
    
    // Check all data in forms
    echo "\n📋 All forms data:\n";
    $sampleQuery = "SELECT * FROM forms";
    $stmt = $pdo->query($sampleQuery);
    $allData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allData as $row) {
        echo "ID: {$row['id']}\n";
        foreach ($row as $key => $value) {
            if ($key != 'id') {
                echo "  {$key}: {$value}\n";
            }
        }
        echo "---\n";
    }
    
    // Check application_forms table if it exists
    $tablesQuery = "SHOW TABLES LIKE 'application_forms'";
    $stmt = $pdo->query($tablesQuery);
    $hasAppForms = $stmt->fetch();
    
    if ($hasAppForms) {
        echo "\n📝 Application forms table structure:\n";
        $structQuery = "DESCRIBE application_forms";
        $stmt = $pdo->query($structQuery);
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($structure as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check sample data
        echo "\n📝 Sample application forms data:\n";
        $sampleQuery = "SELECT * FROM application_forms LIMIT 3";
        $stmt = $pdo->query($sampleQuery);
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sampleData as $row) {
            echo "Application ID: " . (isset($row['application_id']) ? $row['application_id'] : 'N/A') . "\n";
            foreach ($row as $key => $value) {
                if (strlen($value) > 100) {
                    echo "  {$key}: [LONG TEXT - " . strlen($value) . " chars]\n";
                } else {
                    echo "  {$key}: {$value}\n";
                }
            }
            echo "---\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}