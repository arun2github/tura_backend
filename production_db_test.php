<?php
// Simple production database test script
// Upload this file to your production server and run it there

$host = 'localhost';
$dbname = 'u608187177_municipal_prod';
$username = 'u608187177_municipal_prod';
$password = 'Municipal@1468';

echo "Testing database connection...\n";
echo "Host: $host\n";
echo "Database: $dbname\n";
echo "Username: $username\n";
echo "---\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ SUCCESS: Connected to database\n";
    
    // Test if we can query tables
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll();
    echo "Tables found: " . count($tables) . "\n";
    
    // Check if job_applications table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'job_applications'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ job_applications table exists\n";
        
        // Check file_base64 column type
        $stmt = $pdo->prepare("DESCRIBE job_applications");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'file_base64') {
                echo "file_base64 column type: " . $column['Type'] . "\n";
                break;
            }
        }
    } else {
        echo "❌ job_applications table NOT found\n";
    }
    
} catch (PDOException $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}
?>