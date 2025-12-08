<?php
/**
 * Check actual status values used in existing form records
 * This will show us the real status format used in the system
 */

require_once 'vendor/autoload.php';

// Load Laravel configuration
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "🔍 Checking actual status values in form_master_tbl\n";
    echo "=================================================\n\n";
    
    // Get distinct status values from existing records
    $statuses = DB::select("SELECT DISTINCT status, employee_status, ceo_status, COUNT(*) as count 
                           FROM form_master_tbl 
                           WHERE status IS NOT NULL 
                           GROUP BY status, employee_status, ceo_status 
                           ORDER BY count DESC 
                           LIMIT 10");
    
    echo "📊 Most common status combinations:\n";
    echo "-----------------------------------\n";
    foreach($statuses as $status) {
        echo "Status: '{$status->status}' | Employee: '{$status->employee_status}' | CEO: '{$status->ceo_status}' | Count: {$status->count}\n";
    }
    
    echo "\n🔍 Status field lengths:\n";
    echo "------------------------\n";
    $lengths = DB::select("SELECT DISTINCT 
                          LENGTH(status) as status_len,
                          LENGTH(employee_status) as emp_len,
                          LENGTH(ceo_status) as ceo_len
                          FROM form_master_tbl 
                          WHERE status IS NOT NULL 
                          ORDER BY status_len DESC");
    
    foreach($lengths as $len) {
        echo "Status Length: {$len->status_len} | Employee Length: {$len->emp_len} | CEO Length: {$len->ceo_len}\n";
    }
    
    echo "\n📋 Sample records with status values:\n";
    echo "------------------------------------\n";
    $samples = DB::select("SELECT form_id, application_id, status, employee_status, ceo_status 
                          FROM form_master_tbl 
                          WHERE status IS NOT NULL 
                          LIMIT 5");
    
    foreach($samples as $sample) {
        echo "Form ID: {$sample->form_id} | App ID: {$sample->application_id} | Status: '{$sample->status}' | Emp: '{$sample->employee_status}' | CEO: '{$sample->ceo_status}'\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}