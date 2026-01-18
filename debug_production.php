<?php
// Enhanced debugging script for production issues
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Create custom log file
$logFile = __DIR__ . '/debug.log';

function debugLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage . "<br>";
}

try {
    debugLog("=== PRODUCTION DEBUG START ===");
    debugLog("PHP Version: " . PHP_VERSION);
    debugLog("Current directory: " . __DIR__);
    debugLog("Script file: " . __FILE__);
    
    // Check critical files
    $criticalFiles = [
        'vendor/autoload.php' => 'Composer autoloader',
        'app/Http/Controllers/Api/AdmitCardController.php' => 'Admit Card Controller',
        'routes/api.php' => 'API Routes',
        'composer.json' => 'Composer config',
        'composer.lock' => 'Composer lock',
        '.env' => 'Environment file',
        'public/index.php' => 'Laravel entry point',
        'bootstrap/app.php' => 'Laravel bootstrap'
    ];
    
    foreach ($criticalFiles as $file => $description) {
        $fullPath = __DIR__ . '/' . $file;
        $exists = file_exists($fullPath);
        $readable = $exists ? is_readable($fullPath) : false;
        debugLog("$description ($file): " . ($exists ? 'EXISTS' : 'MISSING') . 
                ($readable ? ' & READABLE' : ($exists ? ' & NOT READABLE' : '')));
    }
    
    // Test autoloader
    debugLog("Testing autoloader...");
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        debugLog("Autoloader loaded successfully");
        
        // Test Laravel bootstrap
        if (file_exists(__DIR__ . '/bootstrap/app.php')) {
            debugLog("Testing Laravel bootstrap...");
            $app = require_once __DIR__ . '/bootstrap/app.php';
            debugLog("Laravel app created: " . (is_object($app) ? 'SUCCESS' : 'FAILED'));
            
            if (is_object($app)) {
                debugLog("Laravel version: " . $app->version());
            }
        } else {
            debugLog("Laravel bootstrap missing - this might be the issue!");
        }
        
    } else {
        debugLog("CRITICAL: Autoloader not found!");
    }
    
    // Test basic Laravel functionality
    if (class_exists('Illuminate\Foundation\Application')) {
        debugLog("Laravel classes available: YES");
    } else {
        debugLog("Laravel classes available: NO");
    }
    
    debugLog("=== PRODUCTION DEBUG END ===");
    
} catch (Throwable $e) {
    debugLog("FATAL ERROR: " . $e->getMessage());
    debugLog("Error file: " . $e->getFile() . " line " . $e->getLine());
    debugLog("Stack trace: " . $e->getTraceAsString());
}

// Show log file content if accessible
if (file_exists($logFile)) {
    echo "<hr><h3>Debug Log Contents:</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
}
?>