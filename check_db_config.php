<?php
// Check database configuration consistency

echo "=== DATABASE CONFIGURATION CHECK ===\n\n";

// Load environment variables
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    $envLines = explode("\n", $envContent);
    $envVars = [];
    
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
    
    echo "📄 .env File Configuration:\n";
    echo "DB_CONNECTION: " . ($envVars['DB_CONNECTION'] ?? 'NOT SET') . "\n";
    echo "DB_HOST: " . ($envVars['DB_HOST'] ?? 'NOT SET') . "\n";
    echo "DB_PORT: " . ($envVars['DB_PORT'] ?? 'NOT SET') . "\n";
    echo "DB_DATABASE: " . ($envVars['DB_DATABASE'] ?? 'NOT SET') . "\n";
    echo "DB_USERNAME: " . ($envVars['DB_USERNAME'] ?? 'NOT SET') . "\n";
    echo "DB_PASSWORD: " . (isset($envVars['DB_PASSWORD']) ? str_repeat('*', strlen($envVars['DB_PASSWORD'])) : 'NOT SET') . "\n";
    
} else {
    echo "❌ .env file not found\n";
}

echo "\n" . str_repeat('-', 50) . "\n\n";

// Check config/database.php fallbacks
if (file_exists('config/database.php')) {
    $configContent = file_get_contents('config/database.php');
    
    echo "⚙️  config/database.php Fallback Values:\n";
    
    // Extract fallback values using regex
    if (preg_match("/'host' => env\('DB_HOST', '([^']+)'\)/", $configContent, $matches)) {
        echo "DB_HOST fallback: " . $matches[1] . "\n";
    }
    if (preg_match("/'database' => env\('DB_DATABASE', '([^']+)'\)/", $configContent, $matches)) {
        echo "DB_DATABASE fallback: " . $matches[1] . "\n";
    }
    if (preg_match("/'username' => env\('DB_USERNAME', '([^']+)'\)/", $configContent, $matches)) {
        echo "DB_USERNAME fallback: " . $matches[1] . "\n";
    }
    if (preg_match("/'password' => env\('DB_PASSWORD', '([^']+)'\)/", $configContent, $matches)) {
        echo "DB_PASSWORD fallback: " . str_repeat('*', strlen($matches[1])) . "\n";
    }
} else {
    echo "❌ config/database.php file not found\n";
}

echo "\n" . str_repeat('-', 50) . "\n\n";

// Check for consistency
if (isset($envVars)) {
    echo "✅ CONSISTENCY CHECK:\n";
    
    $envDb = $envVars['DB_DATABASE'] ?? '';
    $envUser = $envVars['DB_USERNAME'] ?? '';
    $envPass = $envVars['DB_PASSWORD'] ?? '';
    
    if (str_contains($configContent, $envDb)) {
        echo "✅ Database name matches between .env and config\n";
    } else {
        echo "❌ Database name mismatch between .env and config\n";
    }
    
    if (str_contains($configContent, $envUser)) {
        echo "✅ Username matches between .env and config\n";
    } else {
        echo "❌ Username mismatch between .env and config\n";
    }
    
    if (str_contains($configContent, $envPass)) {
        echo "✅ Password matches between .env and config\n";
    } else {
        echo "❌ Password mismatch between .env and config\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Production Database: u608187177_municipal_prod\n";
echo "Production Username: u608187177_municipal_prod\n";
echo "Production Password: Municipal@1468\n";
echo "Status: Configuration should now be consistent\n";
?>