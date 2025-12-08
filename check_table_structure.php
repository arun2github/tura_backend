<?php
/**
 * Check form_master_tbl table structure to understand column constraints
 */

require_once 'vendor/autoload.php';

// Load Laravel configuration
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "📋 Form Master Table Structure:\n";
    echo "================================\n";
    
    // Show table structure
    $columns = DB::select("DESCRIBE form_master_tbl");
    
    foreach($columns as $column) {
        echo sprintf("%-20s | %-15s | %-8s | %-8s | %-15s\n", 
            $column->Field, 
            $column->Type, 
            $column->Null, 
            $column->Key ?: 'None',
            $column->Default ?: 'None'
        );
    }
    
    echo "\n🔍 Focusing on status columns:\n";
    echo "------------------------------\n";
    
    $statusColumns = DB::select("DESCRIBE form_master_tbl");
    foreach($statusColumns as $column) {
        if(strpos($column->Field, 'status') !== false) {
            echo "Column: {$column->Field}\n";
            echo "  Type: {$column->Type}\n";
            echo "  Null: {$column->Null}\n";
            echo "  Default: " . ($column->Default ?: 'None') . "\n\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}