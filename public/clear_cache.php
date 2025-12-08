<?php
// clear_cache.php - Run this via browser to clear caches

echo "<h2>Laravel Cache Clear</h2>";

try {
    // Change to Laravel directory
    chdir(__DIR__);
    
    echo "<p>Current directory: " . getcwd() . "</p>";
    
    // Clear route cache
    $output = shell_exec("php artisan route:clear 2>&1");
    echo "<h3>Route Clear:</h3><pre>$output</pre>";
    
    // Clear config cache
    $output = shell_exec("php artisan config:clear 2>&1");
    echo "<h3>Config Clear:</h3><pre>$output</pre>";
    
    // Clear application cache
    $output = shell_exec("php artisan cache:clear 2>&1");
    echo "<h3>Cache Clear:</h3><pre>$output</pre>";
    
    // Clear view cache
    $output = shell_exec("php artisan view:clear 2>&1");
    echo "<h3>View Clear:</h3><pre>$output</pre>";
    
    // List routes
    $output = shell_exec("php artisan route:list | grep job-payment 2>&1");
    echo "<h3>Job Payment Routes:</h3><pre>$output</pre>";
    
    echo "<p><strong>✅ Cache clearing completed!</strong></p>";
    echo "<p>Now test: <a href=\"https://laravelv2.turamunicipalboard.com/job-payment/TMB-2025-JOB1-0002\" target=\"_blank\">https://laravelv2.turamunicipalboard.com/job-payment/TMB-2025-JOB1-0002</a></p>";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>