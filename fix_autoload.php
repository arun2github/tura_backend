<?php
// fix_autoload.php - Put this in Laravel root directory

echo "<h2>Fix Laravel Autoload</h2>";

try {
    // Change to Laravel root directory
    chdir(__DIR__);
    
    echo "<p>Current directory: " . getcwd() . "</p>";
    
    // Check if JobPaymentController exists
    if (file_exists("app/Http/Controllers/JobPaymentController.php")) {
        echo "<p>✅ JobPaymentController.php file exists</p>";
        
        // Read the file and check for class declaration
        $content = file_get_contents("app/Http/Controllers/JobPaymentController.php");
        if (strpos($content, "class JobPaymentController") !== false) {
            echo "<p>✅ JobPaymentController class found in file</p>";
        } else {
            echo "<p>❌ JobPaymentController class missing from file</p>";
        }
    } else {
        echo "<p>❌ JobPaymentController.php file missing</p>";
    }
    
    // Regenerate composer autoload
    echo "<h3>Regenerating Autoload:</h3>";
    $output = shell_exec("composer dump-autoload -o 2>&1");
    echo "<pre>$output</pre>";
    
    // Clear Laravel caches
    echo "<h3>Clearing Caches:</h3>";
    $output = shell_exec("php artisan route:clear 2>&1");
    echo "<p>Route Clear: <pre>$output</pre></p>";
    
    $output = shell_exec("php artisan config:clear 2>&1");
    echo "<p>Config Clear: <pre>$output</pre></p>";
    
    $output = shell_exec("php artisan cache:clear 2>&1");
    echo "<p>Cache Clear: <pre>$output</pre></p>";
    
    // Check routes
    echo "<h3>Checking Routes:</h3>";
    $output = shell_exec("php artisan route:list 2>&1");
    echo "<pre>$output</pre>";
    
    echo "<p><strong>✅ Autoload fix completed!</strong></p>";
    echo "<p>Now test: <a href=\"https://laravelv2.turamunicipalboard.com/job-payment/TMB-2025-JOB1-0002\" target=\"_blank\">https://laravelv2.turamunicipalboard.com/job-payment/TMB-2025-JOB1-0002</a></p>";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>