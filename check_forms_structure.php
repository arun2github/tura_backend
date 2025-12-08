<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "=== Checking Forms Table Structure ===\n";
    
    if (Schema::hasTable('forms')) {
        $columns = Schema::getColumnListing('forms');
        echo "Forms table columns: " . implode(', ', $columns) . "\n\n";
        
        $forms = DB::table('forms')->get();
        echo "Existing forms:\n";
        foreach ($forms as $form) {
            echo "ID: {$form->id}, Name: {$form->name}\n";
        }
    } else {
        echo "Forms table does not exist\n";
    }
    
    echo "\n=== Checking Form Master Table Structure ===\n";
    
    if (Schema::hasTable('form_master_tbl')) {
        $columns = Schema::getColumnListing('form_master_tbl');
        echo "Form Master table columns: " . implode(', ', $columns) . "\n\n";
        
        $sampleRecord = DB::table('form_master_tbl')->first();
        if ($sampleRecord) {
            echo "Sample record structure:\n";
            print_r((array)$sampleRecord);
        }
    } else {
        echo "Form Master table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}