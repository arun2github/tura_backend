<?php
/**
 * Check what status values are used in form_master_tbl
 * This will help us understand the correct format for status fields
 */

require_once 'vendor/autoload.php';

// Load Laravel configuration
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Connect to database
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connected successfully\n\n";
    
    // Check the structure of form_master_tbl
    echo "📋 Form Master Table Structure:\n";
    echo "================================\n";
    $result = DB::select("DESCRIBE form_master_tbl");
    foreach($result as $column) {
        echo "Column: {$column->Field}, Type: {$column->Type}, Null: {$column->Null}, Default: {$column->Default}\n";
    }
    
    echo "\n🔍 Sample Status Values from Existing Records:\n";
    echo "===============================================\n";
    $samples = DB::select("SELECT DISTINCT status, employee_status, ceo_status FROM form_master_tbl LIMIT 20");
    
    foreach($samples as $sample) {
        echo "Status: '{$sample->status}' | Employee Status: '{$sample->employee_status}' | CEO Status: '{$sample->ceo_status}'\n";
    }
    
    echo "\n📊 Status Field Statistics:\n";
    echo "===========================\n";
    $stats = DB::select("SELECT 
        status,
        COUNT(*) as count,
        LENGTH(status) as length
        FROM form_master_tbl 
        GROUP BY status 
        ORDER BY count DESC 
        LIMIT 10");
    
    foreach($stats as $stat) {
        echo "Status: '{$stat->status}' (Length: {$stat->length}) - Used {$stat->count} times\n";
    }
    
    echo "\n📊 Employee Status Field Statistics:\n";
    echo "====================================\n";
    $emp_stats = DB::select("SELECT 
        employee_status,
        COUNT(*) as count,
        LENGTH(employee_status) as length
        FROM form_master_tbl 
        GROUP BY employee_status 
        ORDER BY count DESC 
        LIMIT 10");
    
    foreach($emp_stats as $stat) {
        echo "Employee Status: '{$stat->employee_status}' (Length: {$stat->length}) - Used {$stat->count} times\n";
    }
    
    echo "\n📊 CEO Status Field Statistics:\n";
    echo "===============================\n";
    $ceo_stats = DB::select("SELECT 
        ceo_status,
        COUNT(*) as count,
        LENGTH(ceo_status) as length
        FROM form_master_tbl 
        GROUP BY ceo_status 
        ORDER BY count DESC 
        LIMIT 10");
    
    foreach($ceo_stats as $stat) {
        echo "CEO Status: '{$stat->ceo_status}' (Length: {$stat->length}) - Used {$stat->count} times\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}