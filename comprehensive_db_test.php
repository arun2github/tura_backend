<?php
// Test database connection with different hosts

$databases = [
    [
        'name' => 'Production Database (127.0.0.1)',
        'host' => '127.0.0.1',
        'database' => 'u608187177_municipal_prod',
        'username' => 'u608187177_municipal_prod',
        'password' => 'Municipal@1468'
    ],
    [
        'name' => 'Production Database (localhost)',
        'host' => 'localhost',
        'database' => 'u608187177_municipal_prod',
        'username' => 'u608187177_municipal_prod',
        'password' => 'Municipal@1468'
    ],
    [
        'name' => 'Local MySQL (if installed)',
        'host' => 'localhost',
        'database' => 'test',
        'username' => 'root',
        'password' => ''
    ]
];

echo "=== Database Connection Tests ===\n\n";

foreach ($databases as $db) {
    echo "Testing: {$db['name']}\n";
    echo "Host: {$db['host']}\n";
    echo "Database: {$db['database']}\n";
    echo "Username: {$db['username']}\n";
    echo "---\n";
    
    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};dbname={$db['database']}", 
            $db['username'], 
            $db['password'],
            [PDO::ATTR_TIMEOUT => 5] // 5 second timeout
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ SUCCESS: Connected to {$db['name']}\n";
        
        // Test basic query
        $stmt = $pdo->query('SELECT VERSION() as version');
        $version = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "MySQL Version: " . $version['version'] . "\n";
        
    } catch (PDOException $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "=== IMPORTANT NOTES ===\n";
echo "1. If ALL tests fail, you're trying to connect to a REMOTE database from LOCAL machine\n";
echo "2. Production databases usually don't allow remote connections from local machines\n";
echo "3. You need to test this on your PRODUCTION SERVER where the database is hosted\n";
echo "4. Upload 'production_db_test.php' to your production server and run it there\n";
echo "5. Or set up a local MySQL database for development\n\n";

// Check if MySQL is running locally
echo "=== Local MySQL Check ===\n";
$mysqlProcesses = [];
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    exec('tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul', $mysqlProcesses);
} else {
    exec('ps aux | grep mysql | grep -v grep', $mysqlProcesses);
}

if (count($mysqlProcesses) > 1) {
    echo "✅ MySQL appears to be running locally\n";
} else {
    echo "❌ MySQL does not appear to be running locally\n";
    echo "Consider installing XAMPP, WAMP, or MySQL locally for development\n";
}
?>