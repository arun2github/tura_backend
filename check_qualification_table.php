<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "Checking tura_job_qualification table...\n";
    
    // Check if table exists
    if (!Schema::hasTable('tura_job_qualification')) {
        echo "❌ Table 'tura_job_qualification' does not exist!\n";
        return;
    }
    
    // Count records
    $count = DB::table('tura_job_qualification')->count();
    echo "✅ Table exists with {$count} records\n";
    
    // Get column information
    $columns = DB::select("DESCRIBE tura_job_qualification");
    echo "\nTable structure:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} | Null: {$column->Null} | Default: {$column->Default}\n";
    }
    
    if ($count > 0) {
        echo "\nSample record:\n";
        $sample = DB::table('tura_job_qualification')->first();
        foreach ((array)$sample as $key => $value) {
            $displayValue = is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
            echo "- {$key}: {$displayValue}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}