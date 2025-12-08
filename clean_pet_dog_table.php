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
    
    // Drop pet_dog_registrations table if it exists
    $dropQuery = "DROP TABLE IF EXISTS pet_dog_registrations";
    $stmt = $pdo->exec($dropQuery);
    
    echo "✅ Dropped pet_dog_registrations table (if it existed)\n";
    
    // Remove migration record if it exists
    $deleteMigrationQuery = "DELETE FROM migrations WHERE migration LIKE '%create_pet_dog_registrations_table%'";
    $stmt = $pdo->exec($deleteMigrationQuery);
    
    echo "✅ Removed migration record\n";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}