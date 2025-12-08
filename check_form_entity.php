<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "=== Checking Form Entity Table Structure ===\n";
    
    if (Schema::hasTable('form_entity')) {
        $columns = Schema::getColumnListing('form_entity');
        echo "Form Entity table columns: " . implode(', ', $columns) . "\n\n";
        
        $sampleRecords = DB::table('form_entity')->limit(10)->get();
        echo "Sample form_entity records:\n";
        foreach ($sampleRecords as $record) {
            echo "ID: {$record->id}, Form ID: {$record->form_id}, Parameter: {$record->parameter}, Value: " . substr($record->value, 0, 100) . "...\n";
        }
        
        echo "\n=== Form Entity Parameters Used ===\n";
        $parameters = DB::table('form_entity')
            ->select('parameter')
            ->distinct()
            ->orderBy('parameter')
            ->get();
            
        foreach ($parameters as $param) {
            echo "- {$param->parameter}\n";
        }
    } else {
        echo "Form Entity table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}