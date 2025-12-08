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
    
    // Check exact structure of forms table
    echo "📋 Forms table structure:\n";
    $structQuery = "DESCRIBE forms";
    $stmt = $pdo->query($structQuery);
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columns = [];
    foreach ($structure as $column) {
        echo "  - {$column['Field']} ({$column['Type']}) NULL: {$column['Null']} KEY: {$column['Key']} DEFAULT: {$column['Default']}\n";
        $columns[] = $column['Field'];
    }
    
    // Show current data
    echo "\n📋 Current forms data:\n";
    $dataQuery = "SELECT * FROM forms";
    $stmt = $pdo->query($dataQuery);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($data as $row) {
        echo "  ID: {$row['id']}, Name: " . (isset($row['name']) ? $row['name'] : 'N/A') . "\n";
    }
    
    // Now add Pet Dog Registration if it doesn't exist
    $checkQuery = "SELECT * FROM forms WHERE name LIKE '%Dog%' OR name LIKE '%Pet%'";
    $stmt = $pdo->query($checkQuery);
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($existing)) {
        echo "\n⚠️  Pet/Dog related form already exists:\n";
        foreach ($existing as $form) {
            echo "  ID: {$form['id']}, Name: {$form['name']}\n";
        }
    } else {
        // Build insert query based on available columns
        if (in_array('name', $columns)) {
            $insertQuery = "INSERT INTO forms (name) VALUES (?)";
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute(['Pet Dog Registration']);
            
            $formId = $pdo->lastInsertId();
            
            echo "\n✅ Pet Dog Registration form added successfully!\n";
            echo "   Form ID: $formId\n";
        } else {
            echo "\n❌ Cannot find 'name' column in forms table\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}