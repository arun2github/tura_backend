<?php
// Server capability check
$checks = [];

// Check 1: Basic PHP
$checks['php_version'] = PHP_VERSION;

// Check 2: Required extensions
$required_extensions = ['json', 'mbstring', 'openssl', 'pdo', 'tokenizer'];
foreach ($required_extensions as $ext) {
    $checks["ext_$ext"] = extension_loaded($ext) ? 'OK' : 'MISSING';
}

// Check 3: File permissions
$checks['current_dir'] = __DIR__;
$checks['dir_writable'] = is_writable(__DIR__) ? 'YES' : 'NO';

// Check 4: Memory and limits
$checks['memory_limit'] = ini_get('memory_limit');
$checks['max_execution_time'] = ini_get('max_execution_time');

// Check 5: Error settings
$checks['display_errors'] = ini_get('display_errors') ? 'ON' : 'OFF';
$checks['log_errors'] = ini_get('log_errors') ? 'ON' : 'OFF';

// Output as JSON for easy parsing
header('Content-Type: application/json');
echo json_encode($checks, JSON_PRETTY_PRINT);
?>