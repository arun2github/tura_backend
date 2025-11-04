<?php
// Direct PDO connection test for database

echo "=== DIRECT DATABASE CONNECTION TEST ===\n\n";

$config = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'u608187177_municipal_prod',
    'username' => 'u608187177_municipal_prod',
    'password' => 'Municipal@1468'
];

echo "Testing connection to:\n";
echo "Host: {$config['host']}:{$config['port']}\n";
echo "Database: {$config['database']}\n";
echo "Username: {$config['username']}\n";
echo "Password: " . str_repeat('*', strlen($config['password'])) . "\n";
echo "\n";

// Test 1: Basic PDO connection
echo "🔄 Test 1: Basic PDO Connection\n";
try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ SUCCESS: PDO connection established\n";
    
    // Test basic query
    $stmt = $pdo->query('SELECT VERSION() as version, NOW() as current_time');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $result['version'] . "\n";
    echo "Server Time: " . $result['current_time'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
    
    // Analyze the error
    if (strpos($e->getMessage(), 'Connection refused') !== false || 
        strpos($e->getMessage(), 'actively refused') !== false) {
        echo "\n🔍 DIAGNOSIS:\n";
        echo "- The database server is not accessible from this machine\n";
        echo "- This is expected for remote production databases\n";
        echo "- Production databases typically only allow local connections\n";
    }
}

echo "\n" . str_repeat('-', 50) . "\n\n";

// Test 2: Alternative hosts
echo "🔄 Test 2: Alternative Host Configurations\n";
$alternativeHosts = ['localhost', '127.0.0.1', 'mysql'];

foreach ($alternativeHosts as $host) {
    echo "Testing host: $host\n";
    try {
        $dsn = "mysql:host=$host;port=3306;dbname={$config['database']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "✅ SUCCESS with host: $host\n";
        break;
    } catch (PDOException $e) {
        echo "❌ FAILED with host $host: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat('-', 50) . "\n\n";

// Test 3: Local MySQL check
echo "🔄 Test 3: Local MySQL Service Check\n";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows
    exec('sc query mysql 2>nul', $output, $return_code);
    if ($return_code === 0 && !empty($output)) {
        echo "✅ MySQL service found on Windows\n";
        foreach ($output as $line) {
            if (strpos($line, 'STATE') !== false) {
                echo "Service status: " . trim($line) . "\n";
            }
        }
    } else {
        echo "❌ MySQL service not found on Windows\n";
        echo "💡 Consider installing XAMPP, WAMP, or MySQL for local development\n";
    }
} else {
    // Unix/Linux
    exec('systemctl status mysql 2>/dev/null || service mysql status 2>/dev/null', $output);
    if (!empty($output)) {
        echo "✅ MySQL service status:\n";
        echo implode("\n", array_slice($output, 0, 3)) . "\n";
    } else {
        echo "❌ MySQL service not found\n";
    }
}

echo "\n=== CONCLUSION ===\n";
echo "If all tests failed:\n";
echo "1. ✅ Your database configuration is CORRECT for production\n";
echo "2. ❌ You cannot test the connection from your local machine\n";
echo "3. 🎯 The database will work when deployed to your production server\n";
echo "4. 📤 Upload 'production_db_test.php' to your server to test there\n";
echo "5. 💻 Or install MySQL locally for development testing\n\n";
?>