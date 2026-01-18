<?php
// Minimal error logging test
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Test 1: Basic output
echo "TEST 1: Basic PHP output working\n";

// Test 2: Function test
function testFunction() {
    return "TEST 2: Functions working\n";
}
echo testFunction();

// Test 3: File operations
$testFile = __DIR__ . '/write_test.txt';
if (file_put_contents($testFile, 'File write test')) {
    echo "TEST 3: File write permissions OK\n";
} else {
    echo "TEST 3: File write FAILED\n";
}

// Test 4: Error handling
try {
    echo "TEST 4: Exception handling working\n";
} catch (Exception $e) {
    echo "TEST 4: Exception caught\n";
}

// Test 5: Server info
echo "TEST 5: PHP Version " . PHP_VERSION . "\n";
echo "TEST 5: Server " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "\n";

?>